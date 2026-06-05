<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RateLimiterInterface;
use App\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Override;

use function is_int;
use function max;

/**
 * Cache-backed rate limiter implementation.
 */
final class RateLimiter implements RateLimiterInterface
{
    private readonly string $prefix;

    public function __construct()
    {
        $this->prefix = config('supabase-auth.cache.prefix') . ':rl';
    }

    /**
     * Record an attempt and enforce the configured limit.
     *
     * @throws AuthenticationException
     */
    #[Override]
    public function attempt(string $key, int $maxAttempts, int $decayMinutes = 1): bool
    {

        if (!config('supabase-auth.rate_limiting.enabled')) {
            return true;
        }

        if ($this->tooManyAttempts($key, $maxAttempts)) {
            throw AuthenticationException::rateLimitExceeded($this->availableIn($key));
        }

        $this->hit($key, $decayMinutes);

        return true;
    }

    /**
     * Determine whether the key has exceeded the allowed attempts.
     */
    #[Override]
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Get the number of seconds until attempts become available again.
     */
    #[Override]
    public function availableIn(string $key): int
    {
        $expiry = Cache::get($this->timeKey($key));

        return $expiry !== null ? max(0, $expiry - time()) : 0;
    }

    /**
     * Clear all tracking data for the given key.
     */
    #[Override]
    public function clear(string $key): void
    {
        Cache::forget($this->countKey($key));
        Cache::forget($this->timeKey($key));
    }

    /**
     * Get the remaining number of attempts.
     */
    #[Override]
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    /**
     * Increment the attempt counter.
     */
    #[Override]
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $ttl = $decayMinutes * 60;
        $count = $this->attempts($key);

        if ($count === 0) {
            Cache::put($this->timeKey($key), time() + $ttl, $ttl);
        }

        $count++;
        Cache::put($this->countKey($key), $count, $ttl);

        return $count;
    }

    /**
     * Get the current attempt count.
     */
    #[Override]
    public function attempts(string $key): int
    {
        $count = Cache::get($this->countKey($key), 0);
        return is_int($count) ? $count : 0;
    }

    /**
     * Reset all recorded attempts.
     */
    #[Override]
    public function resetAttempts(string $key): bool
    {
        $this->clear($key);

        return true;
    }

    /**
     * Generate the cache key used for attempt counts.
     */
    private function countKey(string $key): string
    {
        return "{$this->prefix}:count:{$key}";
    }

    /**
     * Generate the cache key used for the window start time.
     */
    private function timeKey(string $key): string
    {
        return "{$this->prefix}:time:{$key}";
    }
}