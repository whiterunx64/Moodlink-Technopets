<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum YearLevel: int
{
    use EnumValues;

    case Unknown = 0;
    case First = 1;
    case Second = 2;
    case Third = 3;
    case Fourth = 4;

    public function toOrdinal(): string
    {
        return match ($this) {
            self::Unknown => 'Not Set',
            self::First => '1st Year',
            self::Second => '2nd Year',
            self::Third => '3rd Year',
            self::Fourth => '4th Year',
        };
    }
}