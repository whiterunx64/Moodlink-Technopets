<?php

declare(strict_types=1);

namespace App\Traits;

trait HasDateTimeDisplay
{
  private function displayPhDate(): string
  {
    return $this->datetime->setTimezone(config('app.timezone'))->format('M d, Y');
  }

  private function displayDate(): string
  {
    return $this->datetime->setTimezone(config('app.timezone'))->toDateString();
  }

  private function displayTime(): string
  {
    return $this->datetime->setTimezone(config('app.timezone'))->format('h:i A');
  }
}