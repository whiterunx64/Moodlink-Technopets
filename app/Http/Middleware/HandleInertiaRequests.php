<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $admin = $user ? Admin::findByUserId($user->id) : null;

        return [
            ...parent::share($request),
            'assets' => [
                'logo' => config('supabase-auth.url') . '/storage/v1/object/public/assets/MailLogo.svg',
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $admin ? trim("{$admin->first_name} {$admin->last_name}") : $user->email,
                    'email' => $user->email,
                    'avatar' => $admin?->avatar,
                    'email_verified_at' => $user->email_confirmed_at,
                    'created_at' => $user->created_at,
                    // TEST JWT TOKEN ALGO
                    // 'access_token' => $user->getAccessToken(),
                ] : null,
            ],
            'flash' => [
                'error' => session('flash_error'),
                'success' => session('flash_success'),
                'student_crendetials' => session('flash_student_credentials'),
            ],
        ];
    }
}