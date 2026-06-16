<?php

namespace App\Enums;

use App\Traits\EnumValues;

enum AdminUserStatus: string
{
    use EnumValues;

    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
        };
    }
    
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
