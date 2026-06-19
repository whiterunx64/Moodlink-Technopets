<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\RateLimiterInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use function sprintf;
use function in_array;
class SupabaseClient
{
    private readonly Client $httpClient;
    private readonly string $url;

    /**
     * Constructor.
     *
     * @param string $url Supabase base URL
     * @param string $anonKey Public anon API key
     * @param string $serviceKey Service role key (admin access)
     * @param LoggerInterface $logger Logger instance
     * @param CircuitBreakerInterface $circuitBreaker Circuit breaker handler
     * @param RateLimiterInterface $rateLimiter Rate limiter handler
     * @param array $config Client configuration (timeouts, retries, etc.)
     */
    public function __construct(
        string $url,
        private readonly string $anonKey,
        private readonly string $serviceKey,
        private readonly LoggerInterface $logger,
        private readonly CircuitBreakerInterface $circuitBreaker,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly array $config = [],
    ) {
        $this->url = rtrim($url, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->url,
            'timeout' => $config['timeout'],
            'connect_timeout' => $config['connect_timeout'],
            'verify' => $config['verify_ssl'],
            'headers' => [
                'User-Agent' => $config['user_agent'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Execute a Supabase API request with retry, rate limit, and circuit breaker.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @param bool $useServiceKey Whether to use service role key
     * @return array Decoded JSON response
     * @throws RuntimeException
     */
    public function request(string $method, string $endpoint, array $options = [], bool $useServiceKey = false): array
    {
        $requestId = $this->generateRequestId();
        $startTime = hrtime(true);

        try {
            $this->rateLimiter->attempt(
                $this->getRateLimitKey($method, $endpoint),
                $this->config['rate_limit_requests'] ?? 100,
                1,
            );

            return $this->circuitBreaker->call(
                fn() => $this->executeRequest($method, $endpoint, $options, $useServiceKey, $requestId),
            );
        } catch (Exception $e) {
            $this->logRequest($method, $endpoint, $requestId, $this->elapsedMs($startTime), false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Internal request execution with retries and response parsing.
     *
     * @throws RuntimeException
     */
    private function executeRequest(
        string $method,
        string $endpoint,
        array $options,
        bool $useServiceKey,
        string $requestId,
    ): array {
        $startTime = hrtime(true);

        $authHeaders = $useServiceKey
            ? ['apikey' => $this->serviceKey, 'Authorization' => "Bearer {$this->serviceKey}"]
            : ['apikey' => $this->anonKey];

        $options['headers'] = [
            ...($options['headers'] ?? []),
            ...$authHeaders,
            'X-Request-ID' => $requestId,
        ];

        $maxAttempts = $this->config['retry_attempts'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 1000;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $body = $this->httpClient->request($method, $endpoint, $options)->getBody()->getContents();

                if ($body === '') {
                    $this->logRequest($method, $endpoint, $requestId, $this->elapsedMs($startTime), true);
                    return [];
                }

                if (!json_validate($body)) {
                    throw new RuntimeException('Invalid JSON response from Supabase API');
                }

                $this->logRequest($method, $endpoint, $requestId, $this->elapsedMs($startTime), true);

                return json_decode($body, associative: true) ?? [];
            } catch (RequestException $e) {
                if ($attempt < $maxAttempts && $this->shouldRetry($e)) {
                    usleep($retryDelay * 1000 * $attempt);
                    continue;
                }

                $this->handleRequestException($e, $method, $endpoint, $requestId);
            }
        }

        throw new RuntimeException('Max retry attempts exceeded');
    }

    /**
     * Determine whether request should be retried based on HTTP status code.
     */
    private function shouldRetry(RequestException $e): bool
    {
        $statusCode = $e->getResponse()?->getStatusCode() ?? 0;

        return $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR
            || in_array($statusCode, [
                Response::HTTP_REQUEST_TIMEOUT,
                Response::HTTP_CONFLICT,
                Response::HTTP_TOO_MANY_REQUESTS,
            ], strict: true);
    }

    /**
     * Handle failed HTTP request and throw final exception.
     *
     * @throws RuntimeException
     */
    private function handleRequestException(
        RequestException $e,
        string $method,
        string $endpoint,
        string $requestId,
    ): never {
        $response = $e->getResponse();
        $statusCode = $response?->getStatusCode() ?? 0;
        $responseBody = null;

        if ($response !== null) {
            try {
                $raw = $response->getBody()->getContents();
                $responseBody = json_validate($raw) ? json_decode($raw, associative: true) : null;
            } catch (Exception) {
                $this->logger->warning('Failed to parse error response JSON', ['request_id' => $requestId]);
            }
        }

        $errorMessage = $responseBody['error_description']
            ?? $responseBody['message']
            ?? $responseBody['msg']        // Supabase GoTrue (auth) errors use this key
            ?? $responseBody['error']
            ?? $e->getMessage();

        $this->logger->error('Supabase API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error' => $errorMessage,
            'request_id' => $requestId,
        ]);

        throw new RuntimeException($errorMessage, $statusCode, $e);
    }

    /**
     * Generate unique request ID for tracing.
     */
    private function generateRequestId(): string
    {
        return sprintf('sup_%s', bin2hex(random_bytes(8)));
    }

    /**
     * Build rate limit key for request tracking.
     */
    private function getRateLimitKey(string $method, string $endpoint): string
    {
        return sprintf('supabase_client:%s:%s', strtolower($method), hash('xxh128', $endpoint));
    }

    private function elapsedMs(int $startNs): float
    {
        return (hrtime(true) - $startNs) / 1e6;
    }

    /**
     * Log API request result.
     */
    private function logRequest(
        string $method,
        string $endpoint,
        string $requestId,
        float $durationMs,
        bool $success,
        ?string $error = null,
    ): void {
        $this->logger->info('Supabase API request completed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'duration_ms' => round($durationMs, 2),
            'request_id' => $requestId,
            'success' => $success,
            ...($error !== null ? ['error' => $error] : []),
        ]);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAnonKey(): string
    {
        return $this->anonKey;
    }

    public function getServiceKey(): string
    {
        return $this->serviceKey;
    }

    /**
     * Perform Supabase health check request.
     *
     * @return array status payload
     */
    public function healthCheck(): array
    {
        try {
            $startTime = hrtime(true);
            $this->request('GET', '/rest/v1/', useServiceKey: true);

            return [
                'status' => 'healthy',
                'response_time_ms' => round($this->elapsedMs($startTime), 2),
                'timestamp' => (new DateTimeImmutable())->format(DATE_ATOM),
            ];
        } catch (Exception $e) {
            $this->logger->error('Supabase health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'timestamp' => (new DateTimeImmutable())->format(DATE_ATOM),
            ];
        }
    }
}
