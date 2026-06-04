<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Auth\Guard;

interface SupabaseGuardInterface extends Guard
{
  public function refreshAccessToken(): bool;
}