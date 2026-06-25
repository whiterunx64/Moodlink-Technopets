<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SummaryReportFilterRequest extends FormRequest
{
    private const array VALID_PERIODS = ['this_week', 'this_month', 'all_time'];
    private const array VALID_TREND_DAYS = [7, 30];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{period: string, tab: string}
     */
    public function filters(): array
    {
        return [
            'period' => $this->periodOrDefault($this->query('period')),
            'tab' => $this->string('tab')->toString() ?: 'overview',
        ];
    }

    /**
     * Student-report mood-trend window in days; defaults to 7 on invalid input.
     */
    public function trendDays(): int
    {
        $days = (int) $this->query('trendDays');

        return in_array($days, self::VALID_TREND_DAYS, true) ? $days : 7;
    }

    private function periodOrDefault(?string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'this_week';
    }
}