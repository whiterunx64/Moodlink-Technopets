<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateSupabase
{
    public function handle(Request $request, Closure $next, string ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }
    /**
     * Check each guard and set the active authenticated guard.
     *
     * If none of the guards are authenticated, the request is rejected.
     *
     * @param  string[] $guards
     */

    protected function authenticate(Request $request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth()->guard($guard)->check()) {
                $this->auth()->shouldUse($guard);
                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * Ensure the user is authenticated for Inertia and web requests.
     * 
     * @param  string[] $guards
     * @throws AuthenticationException
     */
    protected function unauthenticated(Request $request, array $guards): never
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request)
        );
    }

    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    protected function auth(): AuthManager
    {
        return Auth::getFacadeRoot();
    }
}
