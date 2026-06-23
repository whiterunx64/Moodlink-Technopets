<?php

namespace App\Enums;

enum YearLevel: int
{
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