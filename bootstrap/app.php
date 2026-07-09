<?php

use App\Exceptions\InfrastructureException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->web(prepend: [
            \App\Http\Middleware\LogSlowRequests::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetSecurityHeaders::class,
            \App\Http\Middleware\SetCacheHeaders::class,
        ]);

        $middleware->preventRequestForgery(except: [
            'csp-report',
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

            // Surface our descriptive middleware messages as a login toast, but
            // never leak Laravel's bare "Unauthenticated." to the user.
            $message = $e->getMessage();
            if ($message === '' || $message === 'Unauthenticated.') {
                $message = 'Please sign in to continue.';
            }

            return redirect()->route('login')->with('flash_error', $message);
        });

        // Auth/permission HTTP errors: redirect to a working page with a plain
        // toast instead of a dead-end error page. Infrastructure errors (5xx,
        // 429) fall through to their self-contained full-page views, since the
        // server may be down and there's nowhere safe to redirect.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // APIs get the default JSON error
            }

            $messages = [
                403 => 'You don’t have access to that page.',
                404 => 'We couldn’t find that page. Here’s your dashboard instead.',
                405 => 'That action isn’t available here. Please try again from the start.',
            ];

            $status = $e->getStatusCode();
            if (! array_key_exists($status, $messages)) {
                return null; // 429/5xx → self-contained full-page error views
            }

            $target = $request->user() ? route('dashboard') : route('login');

            return redirect()->to($target)->with('flash_error', $messages[$status]);
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