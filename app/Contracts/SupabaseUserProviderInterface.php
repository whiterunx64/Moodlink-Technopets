<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Auth\UserProvider;

interface SupabaseUserProviderInterface extends UserProvider
{
    public function createFromSupabase(array $userData): SupabaseAuthenticatable;
}
