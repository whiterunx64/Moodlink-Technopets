<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

final class PhTime
{
    public const TIMEZONE = 'Asia/Manila';

    public static function toUtc(string $phDateTime): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $phDateTime, self::TIMEZONE)->utc();
    }

    public static function fromUtc(Carbon|string $utcDateTime): Carbon
    {
        return ($utcDateTime instanceof Carbon ? $utcDateTime->copy() : Carbon::parse($utcDateTime))
            ->setTimezone(self::TIMEZONE);
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function todayStartUtc(): Carbon
    {
        return Carbon::today(self::TIMEZONE)->utc();
    }
}