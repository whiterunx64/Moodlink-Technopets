<?php

namespace App\Enums;

use App\Traits\EnumValues;

/**
 * Academic year level, stored as an integer.
 *
 * 1-4 are real year levels; 0 represents an unset / not-yet-assigned level
 * (legacy rows in the database use 0 as a placeholder).
 */
enum YearLevel: int
{
    use EnumValues;

    case Unknown = 0;
    case First   = 1;
    case Second  = 2;
    case Third   = 3;
    case Fourth  = 4;

    /** Ordinal label for UI, e.g. "2nd Year". */
    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Not Set',
            self::First   => '1st Year',
            self::Second  => '2nd Year',
            self::Third   => '3rd Year',
            self::Fourth  => '4th Year',
        };
    }
}
