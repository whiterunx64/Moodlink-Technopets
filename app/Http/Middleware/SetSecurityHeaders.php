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

        $this->preventClickjackingWithFrameOptions($response);
        $this->preventMimeTypeSniffing($response);
        $this->limitReferrerInformationLeakage($response);
        $this->restrictBrowserFeaturePermissions($response);
        $this->hideFrameworkFingerprintHeaders($response);

        if ($this->requestIsServedOverHttps($request)) {
            $this->enforceHttpsWithStrictTransportSecurity($response);
        }

        return $response;
    }

    private function preventClickjackingWithFrameOptions(Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'DENY'); // Prevent iframe-based clickjacking
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