<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Carbon;


trait HasFilters
{
    private static function summaryReportPeriodStart(string $period): ?Carbon
    {
        if ($period === 'this_week') {
            return Carbon::now()->startOfWeek();
        } elseif ($period === 'this_month') {
            return Carbon::now()->startOfMonth();
        } else {
            return null;
        }
    }

    private static function summaryReportPeriodDays(string $period): int
    {
        if ($period === 'this_week') {
            return 7;
        } elseif ($period === 'this_month') {
            return (int) Carbon::now()->daysInMonth;
        } else {
            return 1;
        }
    }
}