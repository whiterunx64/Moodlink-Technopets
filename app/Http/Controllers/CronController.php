<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

final class CronController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cronKey = config('app.cron_key');

        abort_unless(
            filled($cronKey) &&
            hash_equals($cronKey, (string) $request->header('X-Cron-Key')),
            403
        );

        $exitCode = Artisan::call('schedule:run');

        return response()->json([
            'status' => 'ok',
            'exit_code' => $exitCode,
            'output' => Artisan::output(),
            'ran_at' => now()->toIso8601String(),
        ]);
    }
}
