<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'can_reset_password' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            logger()->channel(config('supabase-auth.monitoring.logging.channel'))
                ->warning('Login failed', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'errors' => $e->errors(), // Field => validation messages array
                ]);
                
            if (isset($e->errors()['auth_error'])) {
                return back()->with('flash_error', $e->errors()['auth_error'][0]);
            }

            throw $e;
        }

        $request->session()->regenerate();

        $request->session()->put('auth_started_at', now()->getTimestamp());

        // Record this session as the account's only active one (single-session enforcement).
        $userId = Auth::id();
        if ($userId !== null) {
            Cache::put(
                "auth:active_session:{$userId}",
                $request->session()->getId(),
                config('session.lifetime') * 60,
            );
        }

        return redirect()->intended(config('supabase-auth.auth.provider_redirect'));
    }

    public function logout(Request $request): RedirectResponse
    {
        // Capture before logout clears the authenticated user
        $userId = optional(Auth::user())->getAuthIdentifier();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();

        if ($userId !== null) {
            Cache::forget("auth:active_session:{$userId}");
        }

        logger()->channel(config('supabase-auth.monitoring.logging.channel'))
            ->info('User logged out', [
                'user_id' => $userId, // Supabase user UUID
                'ip' => $request->ip(),
            ]);

        return redirect(config('supabase-auth.auth.logout_redirect'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }
}