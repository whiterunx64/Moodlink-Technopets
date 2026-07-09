<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | MoodLink Android app (APK) download
    |--------------------------------------------------------------------------
    |
    | The public landing page offers the MoodLink APK for sideloading. The
    | actual release URL is NOT baked into the page — instead the landing page
    | links to a server-side route (download.apk) that redirects here. That
    | keeps the release location in one server-controlled place: it can be
    | swapped, validated, and audited without shipping a raw third-party URL
    | to every visitor.
    |
    | 'url' is validated against 'allowed_hosts' before any redirect, so a
    | misconfigured or tampered value can never bounce users to an arbitrary
    | origin.
    |
    */

    'apk' => [
        'url' => env(
            'MOODLINK_APK_URL',
            null,
        ),

        'allowed_hosts' => [
            'github.com',
            'objects.githubusercontent.com', // GitHub release asset CDN
        ],

        'version' => env('MOODLINK_APK_VERSION', '4.0.0'),
        'size' => env('MOODLINK_APK_SIZE', '87.21 MB'),
        'min_os' => env('MOODLINK_APK_MIN_OS', 'Android 8.0+'),
        'updated' => env('MOODLINK_APK_UPDATED', 'Jul 07 2026'),
    ],
];
