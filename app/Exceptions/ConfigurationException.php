<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class ConfigurationException extends Exception
{
  public static function missingKey(string $key): self
  {
    return new self("Missing required configuration key: {$key}.", Response::HTTP_INTERNAL_SERVER_ERROR);
  }

  public static function invalidValue(string $key, mixed $value): self
  {
    return new self("Invalid configuration value for '{$key}': {$value}.", Response::HTTP_INTERNAL_SERVER_ERROR);
  }
}