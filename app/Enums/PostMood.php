<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum PostMood: string
{
    use EnumValues;

    case Drained = 'Drained';
    case Stressed = 'Stressed';
    case Content = 'Content';
    case Excited = 'Excited';

}