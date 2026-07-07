<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationException extends Exception
{
    public static function rateLimitExceeded(?int $retryAfter = null): self
    {
        $message = 'Rate limit exceeded.';

        if ($retryAfter !== null) {
            $message .= " Retry after {$retryAfter} seconds.";
        }

        return new self($message, Response::HTTP_TOO_MANY_REQUESTS);
    }
}