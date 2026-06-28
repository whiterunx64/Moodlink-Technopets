<?php

use App\Exceptions\InfrastructureException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('infrastructureJsonResponse')) {
    function infrastructureJsonResponse(InfrastructureException $e): Response
    {
        return response()->json([
            'message' => $e->getMessage(),
            'code' => $e->errorCode,
            'retryable' => $e->isRetryable(),
        ], $e->getStatusCode());
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetSecurityHeaders::class,
        ]);

        $middleware->alias([
            'supabase.verify-token' => \App\Http\Middleware\EnsureTokenIsValid::class,
            'supabase.revalidate' => \App\Http\Middleware\RevalidateSupabaseUser::class,
            'supabase.require-admin-access' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'supabase.single-session' => \App\Http\Middleware\EnsureSingleActiveSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // fall through to Laravel's default 401 JSON
            }

            return redirect()->route('login')->with('flash_error', $e->getMessage());
        });
        $exceptions->render(function (QueryException|\PDOException $e, Request $request) {
            $infra = InfrastructureException::fromDatabaseError($e);

            if ($infra === null) {
                return null;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return infrastructureJsonResponse($infra);
            }

            throw $infra;
        });

        $exceptions->render(function (InfrastructureException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return infrastructureJsonResponse($e);
            }

            return null;
        });
    })->create();