<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function in_array;

class SummaryReportFilterRequest extends FormRequest
{
    private const array VALID_PERIODS = ['this_week', 'this_month', 'all_time'];
    private const array VALID_TABS = ['overview', 'programs', 'studentsOfConcern'];
    private const array VALID_TREND_DAYS = [7, 30];
    private const int SEARCH_MAX = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'nullable', 'string', Rule::in(self::VALID_PERIODS)],
            'tab' => ['sometimes', 'nullable', 'string', Rule::in(self::VALID_TABS)],
            'trendDays' => ['sometimes', 'nullable', 'integer', Rule::in(self::VALID_TREND_DAYS)],
            'search' => ['sometimes', 'nullable', 'string', 'max:' . self::SEARCH_MAX],
            'from' => ['sometimes', 'nullable', 'string', Rule::in(['concern', 'program'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'That reporting period is not available.',
            'tab.in' => 'That report tab does not exist.',
            'trendDays.in' => 'The trend window must be 7 or 30 days.',
            'search.max' => 'Search terms are limited to :max characters.',
        ];
    }

    /**
     * @return array{period: string, tab: string}
     */
    public function filters(): array
    {
        return [
            'period' => $this->periodOrDefault($this->query('period')),
            'tab' => $this->tabOrDefault($this->query('tab')),
        ];
    }

    /** Trimmed student search term, or null when blank. Length-capped. */
    public function searchTerm(): ?string
    {
        $search = trim((string) $this->query('search'));

        if ($search === '') {
            return null;
        }

        // Collapse whitespace and hard-cap length as a defensive backstop even
        // though validation already rejects over-long input.
        $search = (string) preg_replace('/\s+/', ' ', $search);

        return mb_substr($search, 0, self::SEARCH_MAX);
    }

    /** Mood-trend window in days; 7 or 30 only, defaults to 7. */
    public function trendDays(): int
    {
        $days = (int) $this->query('trendDays');

        return in_array($days, self::VALID_TREND_DAYS, true) ? $days : 7;
    }

    /** Where the user navigated from, for back-navigation. */
    public function fromContext(): string
    {
        return $this->query('from') === 'concern' ? 'concern' : 'program';
    }

    private function periodOrDefault(?string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'this_week';
    }

    private function tabOrDefault(?string $tab): string
    {
        return in_array($tab, self::VALID_TABS, true) ? $tab : 'overview';
    }
}
