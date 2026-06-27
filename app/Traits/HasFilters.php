<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;


trait HasFilters
{
    public static function summaryReportPeriodStart(string $period): ?Carbon
    {
        if ($period === 'this_week') {
            return Carbon::now()->startOfWeek();
        } elseif ($period === 'this_month') {
            return Carbon::now()->startOfMonth();
        } else {
            return null;
        }
    }

    public static function summaryReportPeriodDays(string $period): int
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