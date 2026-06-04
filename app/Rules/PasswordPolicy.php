<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    public static function rule(): Password
    {
        $policy = config('supabase-auth.security.password_policy');

        $rule = Password::min($policy['min_length'] ?? 8);

        if ($policy['require_uppercase'] ?? false) {
            $rule->mixedCase();
        }

        if ($policy['require_lowercase'] ?? false) {
            $rule->letters();
        }

        if ($policy['require_numbers'] ?? false) {
            $rule->numbers();
        }

        if ($policy['require_symbols'] ?? false) {
            $rule->symbols();
        }

        return $rule;
    }
}
