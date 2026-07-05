<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Collects Content-Security-Policy violation reports so a strict policy acts as
 * a sensor: we learn when the CSP blocks something — whether that's a real
 * injection attempt or one of our own features tripping the policy — instead of
 * being blind to it.
 *
 * Browsers post here in two shapes:
 *   - Legacy `report-uri`  → Content-Type: application/csp-report
 *   - Reporting API `report-to` → Content-Type: application/reports+json (a batch)
 */
final class CspReportController extends Controller
{
    public function store(Request $request): Response
    {
        foreach ($this->extractReports($request) as $report) {
            Log::channel('audit')->warning('CSP violation', [
                'blocked_uri' => $report['blocked-uri'] ?? $report['blockedURL'] ?? null,
                'violated_directive' => $report['violated-directive'] ?? $report['effectiveDirective'] ?? null,
                'document_uri' => $report['document-uri'] ?? $report['documentURL'] ?? null,
                'disposition' => $report['disposition'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Nothing to return to the browser; 204 keeps this cheap.
        return response()->noContent();
    }

    /**
     * Normalise both report formats into a flat list of violation bodies.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractReports(Request $request): array
    {
        $payload = $request->json()->all();

        // Legacy: { "csp-report": { ... } }
        if (isset($payload['csp-report']) && is_array($payload['csp-report'])) {
            return [$payload['csp-report']];
        }

        // Reporting API: [ { "type": "csp-violation", "body": { ... } }, ... ]
        if (array_is_list($payload)) {
            return array_values(array_filter(array_map(
                static fn ($entry): ?array => is_array($entry) && isset($entry['body']) && is_array($entry['body'])
                    ? $entry['body']
                    : null,
                $payload,
            )));
        }

        return [];
    }
}
