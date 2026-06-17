<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Probes the app's critical dependencies (database, cache, Supabase) so an
 * uptime monitor or load balancer can tell whether the admin panel is actually
 * able to serve. Each probe is isolated: one failing dependency is reported
 * without breaking the others.
 */
final class HealthChecker
{
    public function __construct(
        private readonly SupabaseClient $supabase,
    ) {
    }

    /**
     * @return array{status: string, checked_at: string, checks: array<string, array<string, mixed>>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'supabase' => $this->checkSupabase(),
        ];

        $allHealthy = ! in_array('unhealthy', array_column($checks, 'status'), true);

        return [
            'status'     => $allHealthy ? 'ok' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'checks'     => $checks,
        ];
    }

    private function checkDatabase(): array
    {
        return $this->probe('database', fn () => DB::select('select 1'));
    }

    private function checkCache(): array
    {
        return $this->probe('cache', function (): void {
            $key = 'health:ping:' . uniqid('', true);

            Cache::put($key, '1', 10);
            $roundTripped = Cache::get($key) === '1';
            Cache::forget($key);

            if (! $roundTripped) {
                throw new RuntimeException('cache read-back failed');
            }
        });
    }

    private function checkSupabase(): array
    {
        $result = $this->supabase->healthCheck();

        return [
            'status'     => ($result['status'] ?? null) === 'healthy' ? 'healthy' : 'unhealthy',
            'latency_ms' => $result['response_time_ms'] ?? null,
        ];
    }

    /**
     * Run a probe, timing it. Failures are logged server-side and reported as
     * "unhealthy" without leaking the error detail to the public endpoint.
     */
    private function probe(string $name, callable $probe): array
    {
        $start = hrtime(true);

        try {
            $probe();

            return ['status' => 'healthy', 'latency_ms' => $this->elapsedMs($start)];
        } catch (Throwable $e) {
            Log::error("Health check failed: {$name}", ['error' => $e->getMessage()]);

            return ['status' => 'unhealthy', 'latency_ms' => $this->elapsedMs($start)];
        }
    }

    private function elapsedMs(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 2);
    }
}
