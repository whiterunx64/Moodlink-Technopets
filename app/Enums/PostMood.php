<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum PostMood: string
{
    use EnumValues;
    case Happy    = 'happy';    case Drained  = 'drained';
    case Sad      = 'sad';
    case Anxious  = 'anxious';
    case Neutral  = 'neutral';
}