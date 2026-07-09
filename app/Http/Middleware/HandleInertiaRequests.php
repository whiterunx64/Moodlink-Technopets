<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\Appointment\QueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** Cache TTL, in seconds, for the shared admin lookup. */
    private const ADMIN_CACHE_TTL = 300;

    /** Cache key for a user's shared admin record. Kept in sync with ProfileService. */
    public static function adminCacheKey(string $userId): string
    {
        return "shared:admin:user:{$userId}";
    }

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
        $admin = $user
            ? Cache::remember(
                self::adminCacheKey($user->id),
                self::ADMIN_CACHE_TTL,
                function () use ($user): ?array {
                    $model = Admin::findByUserId($user->id);

                    return $model === null ? null : [
                        'name' => trim("{$model->first_name} {$model->last_name}"),
                        'avatar' => $model->avatar,
                    ];
                },
            )
            : null;

        $hasSession = $request->hasSession();

        $shared = [
            'assets' => [
                'logo' => config('supabase-auth.url') . '/storage/v1/object/public/assets/MoodlinkLogo.svg',
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $admin['name'] ?? $user->email,
                    'email' => $user->email,
                    'avatar' => $admin['avatar'] ?? null,
                    'email_verified_at' => $user->email_confirmed_at,
                    'created_at' => $user->created_at,
                ] : null,
            ],
            'flash' => $hasSession ? [
                'error' => fn () => $request->session()->pull('flash_error'),
                'success' => fn () => $request->session()->pull('flash_success'),
                'student_credentials' => fn () => $request->session()->pull('flash_student_credentials'),
            ] : [
                'error' => null,
                'success' => null,
                'student_credentials' => null,
            ],
            'checkInAlerts' => $user
                ? fn () => app(QueryService::class)->checkInAlerts()
                : [],
        ];

        return $hasSession
            ? [...parent::share($request), ...$shared]
            : $shared;
    }
}