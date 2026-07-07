<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Contracts\SupabaseGuardInterface;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureTokenIsValid
{
  public function __construct(
    protected readonly SupabaseAuthInterface $supabase,
  ) {
  }

  public function handle(Request $request, Closure $next): mixed
  {
    $user = $this->getAuthenticatedUserWithToken();

    if (!$user) {
      Log::channel(config('supabase-auth.monitoring.logging.channel'))->warning('Token validation failed: no authenticated user or missing token', [
        'ip' => $request->ip(),
        'url' => $request->fullUrl(),
      ]);

      throw new AuthenticationException('Please sign in to continue.');
    }

    if ($this->verifyAccessToken($user) || $this->refreshAndVerifyAccessToken()) {
      return $next($request);
    }

    if ($this->lastFailureWasClockSkew($user)) {
      Log::channel(config('supabase-auth.monitoring.logging.channel'))->warning('Token validation failed: server clock out of sync, session kept', [
        'user_id' => $user->getAuthIdentifier(),
        'ip' => $request->ip(),
        'url' => $request->fullUrl(),
      ]);

      abort(503, 'We are having a temporary problem signing you in. Please try again in a moment.');
    }

    Log::channel(config('supabase-auth.monitoring.logging.channel'))->warning('Token validation failed: token invalid and refresh failed', [
      'user_id' => $user->getAuthIdentifier(),
      'ip' => $request->ip(),
      'url' => $request->fullUrl(),
    ]);

    Auth::logout();
    throw new AuthenticationException('Your session has expired for your security. Please sign in again to pick up where you left off.');
  }

  protected function getAuthenticatedUserWithToken(): ?SupabaseAuthenticatable
  {
    $user = Auth::user();

    if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken()) {
      return $user;
    }

    return null;
  }

  protected function refreshAndVerifyAccessToken(): bool
  {
    $guard = Auth::guard();

    if (!$guard instanceof SupabaseGuardInterface || !$guard->refreshAccessToken()) {
      return false;
    }

    $user = $guard->user();

    if (!$user instanceof SupabaseAuthenticatable || !$user->getAccessToken()) {
      return false;
    }

    $verified = $this->verifyAccessToken($user);

    if ($verified) {
      Log::channel(config('supabase-auth.monitoring.logging.channel'))->info('Access token refreshed and verified successfully', [
        'user_id' => $user->getAuthIdentifier(),
      ]);
    }

    return $verified;
  }

  protected function verifyAccessToken(SupabaseAuthenticatable $user): bool
  {
    return $this->supabase->verifyJwtTokenApiCall($user->getAccessToken())['valid'] === true;
  }

  protected function lastFailureWasClockSkew(SupabaseAuthenticatable $user): bool
  {
    return ($this->supabase->verifyJwtTokenApiCall($user->getAccessToken())['reason'] ?? null) === 'not_yet_valid';
  }
}