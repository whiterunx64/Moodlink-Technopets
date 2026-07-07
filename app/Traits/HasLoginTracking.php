<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\AdminUserStatus;
use Illuminate\Validation\ValidationException;
use RuntimeException;

use function is_int;

trait HasLoginTracking
{
    public function isLocked(): bool
    {
        return $this->locked_until?->isFuture() ?? false;
    }

    public function abortIfLocked(): void
    {
        if ($this->isLocked()) {
            throw ValidationException::withMessages([
                'email' => [
                    trans('auth.locked', ['time' => $this->locked_until->diffForHumans()]),
                ],
            ]);
        }
    }

    public function recordFailedAttempt(): void
    {
        $maxAttempts = config('supabase-auth.rate_limiting.login.max_attempts');
        $lockMinutes = config('supabase-auth.rate_limiting.login.lock_minutes');

        if (!is_int($maxAttempts) || $maxAttempts <= 0 ||
            !is_int($lockMinutes) || $lockMinutes <= 0) {
            throw new RuntimeException('supabase-auth rate limiting config must have positive integer values.');
        }

        $attempts = $this->failed_login_attempts + 1;
        $updates  = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addMinutes($lockMinutes);
        }

        $this->update($updates);
        $this->abortIfLocked();
    }

    public function activateAfterLogin(string $ip): void
    {
        if ($this->trashed()) {
            $this->restore();
        }

        $this->update([
            'status' => AdminUserStatus::Active,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
