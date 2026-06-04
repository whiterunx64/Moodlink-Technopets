<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class CircuitBreakerException extends Exception
{
    public static function circuitOpen(string $service): self
    {
        return new self("Circuit breaker open for service: {$service}.", Response::HTTP_SERVICE_UNAVAILABLE);
    }

    public static function serviceUnavailable(string $service): self
    {
        return new self("Service unavailable: {$service}.", Response::HTTP_SERVICE_UNAVAILABLE);
    }
}