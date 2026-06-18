<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        $activeSessionId = Cache::get("auth:active_session:{$user->getAuthIdentifier()}");

        if ($activeSessionId !== null && $activeSessionId !== $request->session()->getId()) {
            Log::channel(config('supabase-auth.monitoring.logging.channel'))
                ->warning('Session expired due to a new login', [
                    'user_id' => $user->getAuthIdentifier(),
                    'ip' => $request->ip(),
                ]);

            Auth::logout();

            $request->session()->invalidate(); // Remove old session data
            $request->session()->regenerateToken(); // Prevent CSRF token reuse

            return redirect()
                ->route('login')
                ->with('flash_error', 'You were signed out because your account was used to sign in on another device.');
        }

        return $next($request);
    }
}