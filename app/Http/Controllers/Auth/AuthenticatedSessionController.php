<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\SupabaseAuthenticatable;
use App\Guards\SupabaseGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Render the login page (Inertia).
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle Inertia form login — validates via LoginRequest (with rate limiting),
     * then redirects to the intended destination.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * JSON API login — same validation and rate limiting as LoginRequest,
     * returns the authenticated user and Supabase access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $user instanceof SupabaseAuthenticatable
                ? $user->getAccessToken()
                : null,
        ]);
    }

    /**
     * Sign the user out and invalidate the session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Return the currently authenticated user.
     */
    public function user(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Silently refresh the Supabase access token using the stored refresh token.
     */
    public function refresh(): JsonResponse
    {
        $guard = Auth::guard();

        if (!($guard instanceof SupabaseGuard)) {
            return response()->json(['error' => 'Guard does not support token refresh'], 500);
        }

        if (!$guard->refreshAccessToken()) {
            return response()->json([
                'error' => 'Token refresh failed',
                'message' => 'Unable to refresh the access token.',
            ], 401);
        }

        $user = $guard->user();

        return response()->json([
            'message' => 'Token refreshed successfully',
            'user' => $user,
            'access_token' => $user instanceof SupabaseAuthenticatable
                ? $user->getAccessToken()
                : null,
        ]);
    }

    // Backward-compatible alias for the named `logout` route.
    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }
}