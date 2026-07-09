<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy exposed routes
    |--------------------------------------------------------------------------
    |
    | Ziggy serialises the application's routes into the page (via @routes) so
    | the front-end can build URLs with route(). By default it exposes *every*
    | named route, handing an attacker a complete map of the application.
    |
    | To avoid that we explicitly allow-list — by exact name, never by wildcard
    | — only the routes the SPA actually resolves with route(). Wildcards are
    | deliberately avoided: a pattern like 'student-accounts.*' would silently
    | ship any *future* route added to that group to the browser, even a
    | sensitive admin-only one. Listing each name means every exposed route is
    | a conscious decision. Everything else — health checks, the CSP report
    | endpoint, the signed appointment check-in URL (generated server-side),
    | and any future internal route — is never shipped to the browser.
    |
    | When adding a route the front-end must resolve, add its exact name below.
    | If it's only ever used server-side, leave it out.
    |
    | On top of this global allow-list, the root template (app.blade.php) only
    | ships the full admin route map to authenticated users. Guests — i.e. the
    | landing and login pages — receive just the 'public' group below, so an
    | unauthenticated visitor never sees the admin route map.
    |
    */

    'groups' => [
        'public' => [
            'landing',
            'login',
            'download.apk',
        ],
    ],

    'only' => [
        // Entry-point / session routes
        'landing',
        'login',
        'logout',
        'dashboard',
        'download.apk',

        // Notifications
        'notifications.index',
        'notifications.seen',

        // Student accounts
        'student-accounts.index',
        'student-accounts.registration.store',
        'student-accounts.registration.destroy',
        'student-accounts.restrict-access',
        'student-accounts.restore-access',
        'student-accounts.destroy',

        // Post management
        'posts.index',
        'posts.flag',
        'posts.unflag',
        'posts.unreport',
        'pending-posts.approve-safe',
        'pending-posts.approve-flagged',

        // Summary reports
        'reports.index',
        'reports.programs.show',
        'reports.students.show',
        'reports.consult',

        // Appointments
        'appointments.index',
        'appointments.approve',
        'appointments.reject',
        'appointments.slots.store',
        'appointments.slots.destroy',

        // Profile
        'profile.settings',
        'profile.metadata.update',
        'profile.password.update',
        'profile.preferences.update',
        'profile.avatar.update',
        'profile.account.destroy',
    ],
];
