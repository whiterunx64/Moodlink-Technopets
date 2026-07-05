<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $this->setContentSecurityPolicy($response, $nonce);
        $this->observeTrustedTypesViolations($response);
        $this->preventMimeTypeSniffing($response);
        $this->limitReferrerInformationLeakage($response);
        $this->restrictBrowserFeaturePermissions($response);
        $this->hideFrameworkFingerprintHeaders($response);
        $this->enableOriginAgentCluster($response);
        $this->addCrossOriginProtection($response);
        $this->blockLegacyXssFilter($response);

        if ($this->requestIsServedOverHttps($request)) {
            $this->enforceHttpsWithStrictTransportSecurity($response);
        }

        return $response;
    }

    private function setContentSecurityPolicy(Response $response, string $nonce): void
    {
        $supabase = rtrim((string) config('supabase-auth.url'), '/');
        $supabaseSrc = $supabase !== '' ? " {$supabase}" : '';

        $reportUri = route('csp-report');

        $response->headers->set('Reporting-Endpoints', "csp-endpoint=\"{$reportUri}\"");

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self' https://*.supabase.co wss://*.supabase.co{$supabaseSrc}",
            "img-src 'self' data:{$supabaseSrc}",
            "font-src 'self' data:",
            "form-action 'self'",
            "frame-src 'none'",
            "frame-ancestors 'self'",
            "manifest-src 'self'",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            'upgrade-insecure-requests',
            'report-to csp-endpoint',
            "report-uri {$reportUri}",
        ];

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', $csp)
        );
    }

    private function observeTrustedTypesViolations(Response $response): void
    {
        $reportUri = route('csp-report');

        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            implode('; ', [
                "require-trusted-types-for 'script'",
                'trusted-types',
                'report-to csp-endpoint',
                "report-uri {$reportUri}",
            ]),
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

    private function enableOriginAgentCluster(Response $response): void
    {
        $response->headers->set('Origin-Agent-Cluster', '?1');
    }


    private function addCrossOriginProtection(Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
    }

    private function blockLegacyXssFilter(Response $response): void
    {
        $response->headers->set('X-XSS-Protection', '0');
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
