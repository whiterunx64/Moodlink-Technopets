<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Represents a failure in an underlying dependency rather than a user error:
 * the Supabase Postgres database is unreachable, a pooled connection was
 * dropped mid-request, an outbound network call failed, the application
 * reached a state it does not know how to recover from, or a rate limit has
 * been exceeded.
 *
 * Each factory carries a stable machine-readable code (consumed by the error
 * page for tailored copy) plus a message that is safe to show an end user.
 * Detection of low-level driver failures lives in {@see fromDatabaseError()},
 * which maps PostgreSQL SQLSTATE classes onto the right factory.
 */
final class InfrastructureException extends Exception implements HttpExceptionInterface
{
    public const CODE_DB_UNAVAILABLE = 'DB_UNAVAILABLE';
    public const CODE_DB_CONNECTION_LOST = 'DB_CONNECTION_LOST';
    public const CODE_NETWORK_FAILURE = 'NETWORK_FAILURE';
    public const CODE_UNEXPECTED_STATE = 'UNEXPECTED_STATE';
    public const CODE_RATE_LIMITED = 'RATE_LIMIT_EXCEEDED';

    private function __construct(
        public readonly string $errorCode,
        string $userMessage,
        private readonly int $status,
        ?Throwable $previous = null,
        private readonly array $extraHeaders = [],
    ) {
        parent::__construct($userMessage, $status, $previous);
    }

    /**
     * The HTTP status Laravel should render this as (429/503/502/500), which
     * also selects the matching resources/views/errors/{status}.blade.php.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->extraHeaders;
    }

    /**
     * The Supabase Postgres instance could not be reached at all: a paused
     * project, the connection pooler being saturated, DNS failure, or a
     * refused TCP connection.
     */
    public static function databaseUnavailable(?Throwable $previous = null): self
    {
        return new self(
            self::CODE_DB_UNAVAILABLE,
            "We can't reach the database right now. This is usually temporary — please try again in a moment.",
            Response::HTTP_SERVICE_UNAVAILABLE,
            $previous,
        );
    }

    /**
     * A connection to Supabase was established but dropped mid-request — an
     * idle timeout, a server-side disconnect, or the Supavisor pooler
     * recycling the link.
     */
    public static function databaseConnectionLost(?Throwable $previous = null): self
    {
        return new self(
            self::CODE_DB_CONNECTION_LOST,
            "The connection to the database was interrupted. Please retry — your data is safe.",
            Response::HTTP_SERVICE_UNAVAILABLE,
            $previous,
        );
    }

    /**
     * An outbound dependency (Supabase Auth/Storage, a third-party API)
     * failed to respond or timed out.
     */
    public static function networkFailure(string $service = 'a required service', ?Throwable $previous = null): self
    {
        return new self(
            self::CODE_NETWORK_FAILURE,
            "We couldn't reach {$service}. Check your connection and try again shortly.",
            Response::HTTP_BAD_GATEWAY,
            $previous,
        );
    }

    /**
     * The application reached a condition it cannot safely continue from.
     * Use for "this should never happen" guards.
     */
    public static function unexpectedState(string $context = '', ?Throwable $previous = null): self
    {
        $detail = $context !== '' ? " ({$context})" : '';

        return new self(
            self::CODE_UNEXPECTED_STATE,
            "Something went wrong on our end{$detail}. Our team has been notified — please try again.",
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous,
        );
    }

    /**
     * A rate limit was exceeded for the given limiter key (e.g. 'landing').
     *
     * Pass $retryAfter (seconds until the window resets) when known — it is
     * forwarded as the standard Retry-After response header and used by the
     * 429 error page to drive its countdown timer and auto-refresh.
     *
     * Usage inside a RateLimiter::for() response callback:
     *
     *   ->response(function ($request, $headers) use ($limiterKey) {
     *       throw InfrastructureException::rateLimited(
     *           limiter: $limiterKey,
     *           retryAfter: (int) ($headers['Retry-After'] ?? 60),
     *       );
     *   });
     *
     * Or return a response directly by catching it in the handler and calling
     * {@see toResponse()}.
     */
    public static function rateLimited(
        string $limiter = 'default',
        ?int $retryAfter = null,
        ?Throwable $previous = null,
    ): self {
        $headers = ['X-RateLimit-Limiter' => $limiter];

        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string) $retryAfter;
        }

        return new self(
            self::CODE_RATE_LIMITED,
            "You've sent too many requests. Please wait a moment before trying again.",
            Response::HTTP_TOO_MANY_REQUESTS,
            $previous,
            $headers,
        );
    }

    /**
     * Translate a raw Postgres/PDO failure into the right infrastructure
     * exception by inspecting the SQLSTATE and driver message. Returns null
     * when the error is not connectivity-related (e.g. a constraint violation),
     * so callers can let genuine query bugs surface normally.
     *
     * PostgreSQL connection-class SQLSTATEs (class 08) plus libpq's
     * "could not connect" / "server closed the connection" wording cover the
     * failure modes Supabase produces when a project pauses, the pooler is
     * saturated, or a pooled link is recycled.
     */
    public static function fromDatabaseError(QueryException|\PDOException $e): ?self
    {
        $sqlState = self::sqlState($e);
        $message = strtolower($e->getMessage());

        // Connect-time failures (the host can't be reached at all) are checked
        // first by message, because libpq reports several of them under the
        // generic 08006 SQLSTATE — so matching the wording disambiguates a
        // failed *connect* from a connection dropped mid-request.
        $cannotConnect = $sqlState === '08001'   // sqlclient_unable_to_establish_sqlconnection
            || $sqlState === '08004'             // sqlserver_rejected_establishment_of_sqlconnection
            || str_contains($message, 'could not connect')
            || str_contains($message, 'could not translate host name')       // DNS (Supabase host)
            || str_contains($message, 'temporary failure in name resolution') // DNS (glibc)
            || str_contains($message, 'name or service not known')           // DNS
            || str_contains($message, 'connection refused')
            || str_contains($message, 'no route to host')
            || str_contains($message, 'network is unreachable')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'timeout expired')
            || str_contains($message, 'too many clients');                   // pooler saturated

        if ($cannotConnect) {
            return self::databaseUnavailable($e);
        }

        $connectionDropped = $sqlState === '08006'   // connection_failure (mid-request)
            || $sqlState === '08003'                 // connection_does_not_exist
            || $sqlState === '57P01'                 // admin_shutdown
            || $sqlState === '57P02'                 // crash_shutdown
            || str_contains($message, 'server closed the connection')
            || str_contains($message, 'connection already closed')
            || str_contains($message, 'lost connection')
            || str_contains($message, 'broken pipe')
            || str_contains($message, 'ssl connection has been closed');

        if ($connectionDropped) {
            return self::databaseConnectionLost($e);
        }

        return null;
    }

    /**
     * Whether retrying the same request has a reasonable chance of succeeding.
     */
    public function isRetryable(): bool
    {
        return \in_array($this->errorCode, [
            self::CODE_DB_UNAVAILABLE,
            self::CODE_DB_CONNECTION_LOST,
            self::CODE_NETWORK_FAILURE,
            self::CODE_RATE_LIMITED,
        ], true);
    }

    public function retryAfter(): ?int
    {
        $value = $this->extraHeaders['Retry-After'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    private static function sqlState(QueryException|\PDOException $e): ?string
    {
        // QueryException nests the PDOException; both expose SQLSTATE as the
        // first element of errorInfo, falling back to the string $code.
        $errorInfo = $e instanceof QueryException ? $e->errorInfo : ($e->errorInfo ?? null);

        if (\is_array($errorInfo) && isset($errorInfo[0]) && \is_string($errorInfo[0])) {
            return $errorInfo[0];
        }

        return \is_string($e->getCode()) && $e->getCode() !== '' ? $e->getCode() : null;
    }
}