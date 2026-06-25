<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->preventClickjacking($response);
        $this->preventMimeTypeSniffing($response);
        $this->limitReferrerInformationLeakage($response);
        $this->restrictBrowserFeaturePermissions($response);
        $this->hideFrameworkFingerprintHeaders($response);
        $this->addCrossOriginProtection($response);

        if ($this->requestIsServedOverHttps($request)) {
            $this->enforceHttpsWithStrictTransportSecurity($response);
        }

        return $response;
    }

    private function preventClickjacking(Response $response): void
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self' https://*.supabase.co",
            "img-src 'self' data: https://xyxjbqmvxtopeemdxjnq.supabase.co",
            "font-src 'self' data:",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', $csp)
        );
    }
    private function preventMimeTypeSniffing(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff'); // Prevent MIME type guessing attacks
    }

    private function limitReferrerInformationLeakage(Response $response): void
    {
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin'); // Limit URL leakage
    }

    private function restrictBrowserFeaturePermissions(Response $response): void
    {
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ); // Disable unused browser APIs
    }

    private function hideFrameworkFingerprintHeaders(Response $response): void
    {
        $response->headers->remove('X-Powered-By'); // Hide framework information
        $response->headers->remove('Server'); // Hide server information
    }

    private function addCrossOriginProtection(Response $response): void
    {
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
    }

    private function enforceHttpsWithStrictTransportSecurity(Response $response): void
    {
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload',
        ); // Tell browsers to always use HTTPS
    }

    private function requestIsServedOverHttps(Request $request): bool
    {
        return $request->secure(); // Check request protocol before adding HSTS
    }
}