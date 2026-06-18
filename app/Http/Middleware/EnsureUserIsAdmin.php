<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($user = Auth::user()) === null) {
            return redirect()->route('login');
        }

        $admin = Admin::findByUserId($user->id);

        if ($admin === null) {
            return $this->deny($request, 'This account is not authorized to access the admin panel.');
        }

        if (!Admin::isAdministratorRole($user->id)) {
            return $this->deny($request, 'Your account does not have administrator rights.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('flash_error', $message);
    }
}