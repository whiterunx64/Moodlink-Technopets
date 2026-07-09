<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\RateLimiterInterface;
use App\Contracts\AvatarStorageInterface;
use App\Contracts\SupabaseAuthInterface;
use App\Exceptions\ConfigurationException;
use App\Guards\SupabaseGuard;
use App\Services\SupabaseSessionStore;
use App\Providers\SupabaseUserProvider;
use App\Services\CacheManager;
use App\Services\CircuitBreaker;
use App\Services\RateLimiter;
use App\Services\AvatarStorage;
use App\Services\SupabaseAuthApi;
use App\Services\SupabaseClient;
use App\Support\Seo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter as LaravelRateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CircuitBreakerInterface::class, CircuitBreaker::class);
        $this->app->bind(RateLimiterInterface::class, RateLimiter::class);

        $this->app->singleton(SupabaseClient::class, fn($app) => new SupabaseClient(
            config('supabase-auth.url') ?? throw ConfigurationException::missingKey('SUPABASE_URL'),
            config('supabase-auth.anon_key') ?? throw ConfigurationException::missingKey('SUPABASE_ANON_KEY'),
            config('supabase-auth.service_key') ?? throw ConfigurationException::missingKey('SUPABASE_SERVICE_KEY'),
            $app['log']->channel(config('supabase-auth.monitoring.logging.channel')),
            $app->make(CircuitBreakerInterface::class),
            $app->make(RateLimiterInterface::class),
            config('supabase-auth.client'),
        ));

        $this->app->singleton(CacheManager::class, fn($app) => new CacheManager(
            $app['cache']->store(config('supabase-auth.cache.store')),
            $app['log']->channel(config('supabase-auth.monitoring.logging.channel')),
            config('supabase-auth.cache'),
        ));

        $this->app->singleton(SupabaseAuthInterface::class, fn($app) => new SupabaseAuthApi(
            $app->make(SupabaseClient::class),
            $app->make(CacheManager::class),
            $app['log']->channel(config('supabase-auth.monitoring.logging.channel')),
            $app['log']->channel(config('supabase-auth.monitoring.logging.admin_channel')),
        ));

        $this->app->singleton(AvatarStorageInterface::class, fn($app) => new AvatarStorage(
            $app->make(SupabaseClient::class),
            $app['log']->channel(config('supabase-auth.monitoring.logging.channel')),
            config('supabase-auth.storage.avatar_bucket'),
        ));
    }

    public function boot(): void
    {
        LaravelRateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            $onExceeded = function (Request $request, array $headers) {
                Log::warning('Login rate limit exceeded', [
                    'ip' => $request->ip(),
                    'email' => (string) $request->input('email'),
                    'user_agent' => $request->userAgent(),
                ]);

                $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));

                throw ValidationException::withMessages([
                    'throttle' => trans('auth.throttle', [
                        'seconds' => $seconds,
                        'minutes' => (int) ceil($seconds / 60),
                    ]),
                    'retryAfter' => (string) $seconds,
                ]);
            };

            return [
                // absorbs shared-NAT traffic without over-penalising it.
                Limit::perMinute(5)->by($request->ip())->response($onExceeded),
                // a single email is harder to brute-force.
                Limit::perMinute(3)->by(mb_strtolower($email) . '|' . $request->ip())->response($onExceeded),
            ];
        });

        LaravelRateLimiter::for('csp-report', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        LaravelRateLimiter::for('landing', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function ($request, $headers) {

                    Log::warning('Landing page rate limit exceeded', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()
                        ->view('errors.429', [
                            'headers' => $headers,
                            'retryAfter' => $retryAfter,
                        ], 429)
                        ->withHeaders($headers);
                });
        });

        LaravelRateLimiter::for('download', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function ($request, $headers) {

                    Log::warning('APK download rate limit exceeded', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()
                        ->view('errors.429', [
                            'headers' => $headers,
                            'retryAfter' => $retryAfter,
                        ], 429)
                        ->withHeaders($headers);
                });
        });
        
        LaravelRateLimiter::for('report-export', function (Request $request) {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            $limitResponse = function ($request, array $headers) {
                Log::warning('Report export rate limit exceeded', [
                    'user_id' => $request->user()?->getAuthIdentifier(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'path' => $request->path(),
                ]);

                $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                return response()
                    ->view('errors.429', [
                        'headers' => $headers,
                        'retryAfter' => $retryAfter,
                    ], 429)
                    ->withHeaders($headers);
            };

            return [
                Limit::perMinute(6)->by("report-export:min:{$key}")->response($limitResponse),
                Limit::perDay(60)->by("report-export:day:{$key}")->response($limitResponse),
            ];
        });

        // Force HTTPS URLs in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        View::composer('app', function ($view): void {
            $view->with('seo', Seo::forPath(request()->path()));
        });

        Auth::provider('supabase', fn($app, array $config) => new SupabaseUserProvider(
            $app->make(SupabaseAuthInterface::class),
            $config['model'] ?? \App\Models\User::class,
        ));

        Auth::extend('supabase', function ($app, string $name, array $config) {
            $guard = new SupabaseGuard(
                $name,
                Auth::createUserProvider($config['provider']),
                new SupabaseSessionStore($app['session.store']),
                $app->make(SupabaseAuthInterface::class),
            );

            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}