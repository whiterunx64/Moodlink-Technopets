<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum AdminUserStatus: string
{
    use EnumValues;

    case Active = 'active';
    case Inactive = 'inactive';

}