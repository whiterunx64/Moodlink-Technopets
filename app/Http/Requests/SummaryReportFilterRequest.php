<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SummaryReportFilterRequest extends FormRequest
{
  private const array VALID_PERIODS = ['this_week', 'this_month', 'all_time'];

  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [];
  }

  public function filters(): array
  {
    return [
      'period' => $this->validPeriod($this->query('period')),
      'tab' => $this->string('tab')->toString() ?: 'overview',
    ];
  }

  private function validPeriod(?string $period): string
  {
    return in_array($period, self::VALID_PERIODS, true) ? $period : 'this_week';
  }
}