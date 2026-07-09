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
use Illuminate\Support\Facades\Cache;
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

    // Hard ceiling on total session age, independent of token refreshes.
    if ($this->sessionExceededAbsoluteLifetime($request)) {
      Log::channel(config('supabase-auth.monitoring.logging.channel'))->info('Session ended: absolute lifetime reached', [
        'user_id' => $user->getAuthIdentifier(),
        'ip' => $request->ip(),
      ]);

      Auth::logout();
      throw new AuthenticationException('Your session has reached its time limit. Please sign in again.');
    }

    if ($this->verifyAccessToken($user)) {
      return $next($request);
    }

    if ($this->refreshAndVerifyAccessToken()) {

      $this->rotateSession($request);

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

  protected function sessionExceededAbsoluteLifetime(Request $request): bool
  {
    $max = (int) config('supabase-auth.auth.absolute_lifetime', 0);

    if ($max <= 0 || !$request->hasSession()) {
      return false; // Feature disabled or no session to age out.
    }

    $startedAt = $request->session()->get('auth_started_at');

    // Sessions that predate this feature have no anchor — stamp one now so they
    // age out from here rather than being force-logged-out immediately.
    if ($startedAt === null) {
      $request->session()->put('auth_started_at', now()->getTimestamp());

      return false;
    }

    return (now()->getTimestamp() - (int) $startedAt) > $max;
  }

  
  protected function rotateSession(Request $request): void
  {
    if (!$request->hasSession()) {
      return;
    }

    $userId = Auth::user()?->getAuthIdentifier();
    $cacheKey = $userId !== null ? "auth:active_session:{$userId}" : null;

    // Only carry the cache forward if THIS session is the recorded active one,
    // so we never clobber another device's entry.
    $isActiveSession = $cacheKey !== null
      && Cache::get($cacheKey) === $request->session()->getId();

    $request->session()->migrate(true);     // new ID, keep data, delete old store
    $request->session()->regenerateToken(); // new CSRF token

    if ($isActiveSession) {
      Cache::put(
        $cacheKey,
        $request->session()->getId(),
        (int) config('session.lifetime') * 60,
      );
    }
  }

  protected function lastFailureWasClockSkew(SupabaseAuthenticatable $user): bool
  {
    return ($this->supabase->verifyJwtTokenApiCall($user->getAccessToken())['reason'] ?? null) === 'not_yet_valid';
  }
}