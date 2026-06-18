<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HealthChecker;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function check(HealthChecker $health): JsonResponse
    {
        abort_unless(config('supabase-auth.monitoring.health_checks.enabled'), 404);

        $result = $health->run();
        $status = $result['status'] === 'ok' ? 200 : 503;

        return response()->json($result, $status);
    }
}