<?php

declare(strict_types=1);

namespace App\Enums;

enum JwtAlgorithm: string
{
    case HS256 = 'HS256';
    case HS384 = 'HS384';
    case HS512 = 'HS512';
    case ES256 = 'ES256';
    case ES384 = 'ES384';
    case ES512 = 'ES512';
    case RS256 = 'RS256';
    case RS384 = 'RS384';
    case RS512 = 'RS512';

    public function isAsymmetric(): bool
    {
        return match ($this) {
            self::ES256, self::ES384, self::ES512,
            self::RS256, self::RS384, self::RS512 => true,
            default => false,
        };
    }

    public static function fromConfig(): self
    {
        return self::from(config('supabase-auth.jwt.algorithm'));
    }
}
