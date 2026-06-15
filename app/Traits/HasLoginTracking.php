<?php

declare(strict_types=1);

namespace App\Traits;

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
                'auth_error' => [
                    'Your account is locked until ' . $this->locked_until->diffForHumans() . '.'
                ],
            ]);
        }
    }

    public function recordFailedAttempt(): void
    {
        $maxAttempts  = config('supabase-auth.rate_limiting.login.max_attempts');
        $decayMinutes = config('supabase-auth.rate_limiting.login.decay_minutes');

        if (!is_int($maxAttempts) || $maxAttempts <= 0 ||
            !is_int($decayMinutes) || $decayMinutes <= 0) {
            throw new RuntimeException('supabase-auth rate limiting config must have positive integer values.');
        }

        $attempts = $this->failed_login_attempts + 1;
        $updates  = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addMinutes($decayMinutes);
        }

        $this->update($updates);
    }

    public function activateAfterLogin(string $ip): void
    {
        if ($this->trashed()) {
            $this->restore();
        }

        $this->update([
            'status' => 'active',
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
