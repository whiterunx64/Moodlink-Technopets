<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\RateLimiterInterface;
use App\Contracts\SupabaseAuthInterface;
use App\Exceptions\ConfigurationException;
use App\Guards\SupabaseGuard;
use App\Services\SupabasePersistentStorage;
use App\Providers\SupabaseUserProvider;
use App\Services\CacheManager;
use App\Services\CircuitBreaker;
use App\Services\RateLimiter;
use App\Services\SupabaseAuth;
use App\Services\SupabaseClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton(SupabaseAuthInterface::class, fn($app) => new SupabaseAuth(
            $app->make(SupabaseClient::class),
            $app->make(CacheManager::class),
            $app['log']->channel(config('supabase-auth.monitoring.logging.channel')),
        ));
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Auth::provider('supabase', fn($app, array $config) => new SupabaseUserProvider(
            $app->make(SupabaseAuthInterface::class),
            $config['model'] ?? \App\Models\User::class,
        ));

        Auth::extend('supabase', function ($app, string $name, array $config) {
            $guard = new SupabaseGuard(
                $name,
                Auth::createUserProvider($config['provider']),
                new SupabasePersistentStorage($app['session.store']),
                $app->make(SupabaseAuthInterface::class),
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
