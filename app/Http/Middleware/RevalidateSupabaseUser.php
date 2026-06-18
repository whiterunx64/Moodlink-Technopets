<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class RevalidateSupabaseUser
{
    private const SESSION_EXPIRED = 'Your login has expired. Please sign in again to continue.';

    public function __construct(
        protected readonly SupabaseAuthInterface $supabase,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (!$user instanceof SupabaseAuthenticatable || !$user->getAccessToken()) {
            throw new AuthenticationException(self::SESSION_EXPIRED);
        }

        if ($this->confirmUserStillValidWithSupabase($user)) {
            return $next($request);
        }

        Auth::logout();
        throw new AuthenticationException(self::SESSION_EXPIRED);
    }

    protected function confirmUserStillValidWithSupabase(SupabaseAuthenticatable $user): bool
    {
        try {
            $supabaseUser = $this->supabase->getUser($user->getAccessToken());
        } catch (Exception $e) {
            Log::channel(config('supabase-auth.monitoring.logging.channel'))->warning('Supabase user revalidation failed', [
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return isset($supabaseUser['id']);
    }
}