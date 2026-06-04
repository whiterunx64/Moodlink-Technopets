<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Contracts\SupabaseGuardInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Validates the Supabase access token once per request on protected routes.
 *
 * The guard's user() is session-only (no API call). This middleware owns the
 * single Supabase API call per request: verifyToken(). If the token is expired
 * it attempts one silent refresh via the guard before giving up.
 *
 * Applied after AuthenticateSupabase in the protected route group.
 */
class EnsureTokenIsValid
{
  public function __construct(
    protected readonly SupabaseAuthInterface $supabase,
  ) {
  }

  public function handle(Request $request, Closure $next): mixed
  {
    /** @var SupabaseAuthenticatable|null $user */
    $user = Auth::user();

    if (!$user instanceof SupabaseAuthenticatable || !$user->getAccessToken()) {
      return $this->unauthorized($request);
    }

    // Single Supabase API call per request, validates the JWT server-side.
    $validation = $this->supabase->verifyToken($user->getAccessToken());

    if (!$validation['valid']) {
      $guard = Auth::guard();

      // Attempt one silent refresh before giving up.
      if ($guard instanceof SupabaseGuardInterface && $guard->refreshAccessToken()) {

        $user = $guard->user();  // cache reset forces user to be loaded again from session

        if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken()) {
          $validation = $this->supabase->verifyToken($user->getAccessToken());

          if ($validation['valid']) {
            return $next($request);
          }
        }
      }

      Auth::logout();

      return $this->unauthorized($request);
    }

    return $next($request);
  }

  /**
   * Redirect to login for Inertia/browser requests, JSON 401 for API calls.
   */
  protected function unauthorized(Request $request): JsonResponse|RedirectResponse
  {
    if ($request->expectsJson()) {
      return response()->json([
        'error' => 'Token invalid or expired',
        'message' => 'Please login again.',
      ], 401);
    }

    return redirect()->route('login');
  }
}
