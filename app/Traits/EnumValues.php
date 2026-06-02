<?php

namespace App\Traits;

/**
 * @mixin \UnitEnum
 * @method static static[] cases()
 */
trait EnumValues
{
    public static function values(): array
    {
        return array_map(fn(\UnitEnum $case) => $case->name, static::cases());
    }
}