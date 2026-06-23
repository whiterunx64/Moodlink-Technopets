<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

final class MailDeliveryException extends RuntimeException
{
    private function __construct(
        private readonly string $reason,
        string $summary,
        Throwable $previous,
    ) {
        parent::__construct($summary, 0, $previous);
    }

    public static function smtpHostUnresolved(TransportExceptionInterface $previous): self
    {
        return new self(
            'dns_unresolved',
            'SMTP host could not be resolved.',
            $previous,
        );
    }

    public static function smtpConnectionRefused(TransportExceptionInterface $previous): self
    {
        return new self(
            'connection_refused',
            'SMTP connection could not be established.',
            $previous,
        );
    }

    public static function smtpAuthenticationRejected(TransportExceptionInterface $previous): self
    {
        return new self(
            'auth_failed',
            'SMTP authentication was rejected.',
            $previous,
        );
    }

    public static function recipientNotVerified(TransportExceptionInterface $previous): self
    {
        return new self(
            'recipient_not_verified',
            'Recipient address was not verified by the provider.',
            $previous,
        );
    }

    public static function transportError(TransportExceptionInterface $previous): self
    {
        return new self(
            'transport_error',
            'Mail transport error.',
            $previous,
        );
    }

    /**
     * Classify a raw SMTP transport failure into the matching named exception.
     */
    public static function from(TransportExceptionInterface $previous): self
    {
        $message = $previous->getMessage();

        return match (true) {
            str_contains($message, 'getaddrinfo'),
            str_contains($message, 'php_network_getaddresses')
            => self::smtpHostUnresolved($previous),

            str_contains($message, 'Connection could not be established'),
            str_contains($message, 'stream_socket_client')
            => self::smtpConnectionRefused($previous),

            str_contains($message, '535'),
            str_contains($message, 'Authentication Credentials Invalid')
            => self::smtpAuthenticationRejected($previous),

            str_contains($message, 'not verified'),
            str_contains($message, '554')
            => self::recipientNotVerified($previous),

            default => self::transportError($previous),
        };
    }

    /**
     * Stable, groupable code for the failure (e.g. for `grep reason=auth_failed`).
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Short one-line description, safe to put in the log headline.
     */
    public function summary(): string
    {
        return $this->getMessage();
    }

    /**
     * The full raw transport message, for forensic detail in log context.
     */
    public function detail(): string
    {
        return $this->getPrevious()?->getMessage() ?? $this->getMessage();
    }
}