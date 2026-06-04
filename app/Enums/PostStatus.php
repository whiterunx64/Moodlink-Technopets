<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum PostStatus: string
{
    use EnumValues;

    case Flagged = 'flagged';
    case Safe    = 'safe';
}