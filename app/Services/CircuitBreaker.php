<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CircuitBreakerInterface;
use App\Exceptions\CircuitBreakerException;
use Closure;
use RuntimeException;
use Illuminate\Support\Facades\Cache;
use Override;
use Throwable;

use function is_int;

/**
 * Circuit breaker implementation for protecting external service calls.
 */
final class CircuitBreaker implements CircuitBreakerInterface
{
    private readonly int $failureThreshold;
    private readonly int $recoveryTimeout;
    private readonly string $prefix;

    public function __construct()
    {
        $failureThreshold = config('supabase-auth.circuit_breaker.failure_threshold');
        $recoveryTimeout  = config('supabase-auth.circuit_breaker.recovery_timeout');

        if (!is_int($failureThreshold) || $failureThreshold <= 0 ||
            !is_int($recoveryTimeout) || $recoveryTimeout <= 0) {
            throw new RuntimeException('supabase-auth circuit_breaker config must have positive integer values.');
        }

        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout  = $recoveryTimeout;
        $this->prefix = config('supabase-auth.cache.prefix') . ':cb';
    }

    /**
     * Execute a callback through the circuit breaker.
     *
     * @throws CircuitBreakerException
     * @throws Throwable
     */
    #[Override]
    public function call(Closure $callback, string $service = 'default'): mixed
    {
        if (!config('supabase-auth.circuit_breaker.enabled')) {
            return $callback();
        }

        if ($this->isOpen($service)) {
            throw CircuitBreakerException::circuitOpen($service);
        }

        try {
            $result = $callback();
            $this->recordSuccess($service);
            return $result;
        } catch (Throwable $e) {
            $this->recordFailure($service);
            throw $e;
        }
    }

    /**
     * Determine whether the circuit is open.
     */
    #[Override]
    public function isOpen(string $service = 'default'): bool
    {
        $openedAt = Cache::get($this->key($service, 'opened_at'));

        if ($openedAt === null) {
            return false;
        }

        if (time() - $openedAt >= $this->recoveryTimeout) {
            Cache::forget($this->key($service, 'opened_at'));
            return false;
        }

        return true;
    }

    /**
     * Determine whether the circuit is half-open.
     */
    #[Override]
    public function isHalfOpen(string $service = 'default'): bool
    {
        return !$this->isOpen($service) && $this->getFailureCount($service) > 0;
    }

    /**
     * Determine whether the circuit is closed.
     */
    #[Override]
    public function isClosed(string $service = 'default'): bool
    {
        return !$this->isOpen($service) && $this->getFailureCount($service) === 0;
    }

    /**
     * Record a successful operation and reset circuit state.
     */
    #[Override]
    public function recordSuccess(string $service = 'default'): void
    {
        Cache::forget($this->key($service, 'failures'));
        Cache::forget($this->key($service, 'opened_at'));
    }

    /**
     * Record a failed operation and open the circuit if the threshold is reached.
     */
    #[Override]
    public function recordFailure(string $service = 'default'): void
    {
        $failures = $this->getFailureCount($service) + 1;
        $ttl = $this->recoveryTimeout * 2;

        Cache::put($this->key($service, 'failures'), $failures, $ttl);

        if ($failures >= $this->failureThreshold) {
            Cache::put($this->key($service, 'opened_at'), time(), $ttl);
        }
    }

    /**
     * Get the current failure count.
     */
    #[Override]
    public function getFailureCount(string $service = 'default'): int
    {
        $count = Cache::get($this->key($service, 'failures'), 0);
        return is_int($count) ? $count : 0;
    }

    /**
     * Reset the circuit breaker state.
     */
    #[Override]
    public function reset(string $service = 'default'): void
    {
        $this->recordSuccess($service);
    }

    /**
     * Generate a cache key for the service and state.
     */
    private function key(string $service, string $suffix): string
    {
        return "{$this->prefix}:{$service}:{$suffix}";
    }
}