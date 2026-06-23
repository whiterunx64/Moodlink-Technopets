<?php

declare(strict_types=1);

namespace App\Traits;

trait HasDateTimeDisplay
{
  public const DISPLAY_TIMEZONE = 'Asia/Manila';

  protected function phFormat(string $format): string
  {
    return $this->datetime->copy()->setTimezone(self::DISPLAY_TIMEZONE)->format($format);
  }
}