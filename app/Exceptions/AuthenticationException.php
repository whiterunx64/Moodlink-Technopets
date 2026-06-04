<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid authentication credentials provided.', Response::HTTP_UNAUTHORIZED);
    }

    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired.', Response::HTTP_UNAUTHORIZED);
    }

    public static function tokenInvalid(): self
    {
        return new self('Authentication token is invalid.', Response::HTTP_UNAUTHORIZED);
    }

    public static function userNotFound(): self
    {
        return new self('User not found.', Response::HTTP_NOT_FOUND);
    }

    public static function emailNotVerified(): self
    {
        return new self('Email address not verified.', Response::HTTP_FORBIDDEN);
    }

    public static function accountDisabled(): self
    {
        return new self('User account has been disabled.', Response::HTTP_FORBIDDEN);
    }

    public static function rateLimitExceeded(?int $retryAfter = null): self
    {
        $message = 'Rate limit exceeded.';

        if ($retryAfter !== null) {
            $message .= " Retry after {$retryAfter} seconds.";
        }

        return new self($message, Response::HTTP_TOO_MANY_REQUESTS);
    }
}