<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface SupabaseAuthenticatable extends Authenticatable
{
    public function setSupabaseData(array $data): static;

    public function setAccessToken(string $token): static;

    public function getAccessToken(): ?string;
}
