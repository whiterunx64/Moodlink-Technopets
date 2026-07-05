<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;
        $threshold = (int) config('supabase-auth.monitoring.slow_request_threshold_ms', 1000);

        if ($threshold > 0 && $elapsedMs >= $threshold) {
            Log::channel(config('supabase-auth.monitoring.logging.channel'))
                ->warning('Slow request', [
                    'method' => $request->method(),
                    'route' => $request->route()?->getName() ?? $request->path(),
                    'duration_ms' => round($elapsedMs, 1),
                    'status' => $response->getStatusCode(),
                    'user_id' => optional($request->user())->getAuthIdentifier(),
                ]);
        }

        return $response;
    }
}