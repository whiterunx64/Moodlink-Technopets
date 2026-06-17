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

class EnsureTokenIsValid
{
  private const SESSION_EXPIRED = 'Your session has expired or is no longer valid. Please sign in again to continue.';

  public function __construct(
    protected readonly SupabaseAuthInterface $supabase,
  ) {
  }

  public function handle(Request $request, Closure $next): mixed
  {
    $user = $this->getAuthenticatedUserWithToken();

    if (!$user) {
      throw new AuthenticationException(self::SESSION_EXPIRED);
    }

    if ($this->verifyAccessToken($user) || $this->refreshAndVerifyAccessToken()) {
      return $next($request);
    }

    Auth::logout();
    throw new AuthenticationException(self::SESSION_EXPIRED);
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

    return $this->verifyAccessToken($user);
  }

  protected function verifyAccessToken(SupabaseAuthenticatable $user): bool
  {
    return $this->supabase->verifyToken($user->getAccessToken())['valid'] === true;
  }
}
