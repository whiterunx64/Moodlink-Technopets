<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class DownloadController extends Controller
{
    public function apk(): RedirectResponse
    {
        $url = (string) config('download.apk.url');
        $allowedHosts = (array) config('download.apk.allowed_hosts', []);

        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        $isSafe = $host !== null
            && strtolower((string) $scheme) === 'https'
            && in_array(strtolower($host), array_map('strtolower', $allowedHosts), true);

        if (! $isSafe) {
            Log::error('Blocked APK download: configured URL is not an allowed HTTPS release host.', [
                'configured_url' => $url,
            ]);

            abort(503, 'The download is temporarily unavailable.');
        }
        
        return redirect()->away($url, 302, [
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
