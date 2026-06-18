<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\StudentStatus;
use App\Enums\YearLevel;

trait HasStudentStatus
{
  private function displayYearLevel(): string
  {
    $label = YearLevel::tryFrom($this->year_level)?->toOrdinal();

    if ($label !== null) {
      return $label;
    } else {
      return 'Not Set';
    }
  }

  private function displayAccountStatus(): string
  {
    if ($this->status === StudentStatus::Verified) {
      return 'active';
    } else {
      return 'suspended';
    }
  }

  private function displayVerificationStatus(): string
  {
    if ($this->status === StudentStatus::Suspended) {
      return StudentStatus::Verified->value;
    }

    return $this->status->value;
  }
}