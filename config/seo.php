<?php

declare(strict_types=1);

return [

    // ── Site-wide defaults ────────────────────────────────────────────────
    'defaults' => [
        'title' => 'MoodLink — FEU Diliman Student Wellness',
        'description' => 'MoodLink is FEU Diliman\'s student wellness platform. It lets the '
            . 'guidance office track student moods, review posts, and manage counseling '
            . 'appointments in one secure place.',
        'keywords' => 'MoodLink, FEU Diliman, FEU Diliman guidance office, student wellness, '
            . 'student mental health, mood tracking, counseling appointments',
        'image' => '/og-image.png',      // 1200×630 recommended; place in public/
        'robots' => 'noindex, nofollow', // private by default; public paths override below
        'type' => 'website',
    ],

    // ── Regex path → metadata (first match wins) ──────────────────────────
    'paths' => [

        // Landing page — the main public page.
        '#^/$#' => [
            'title' => 'MoodLink — FEU Diliman Student Wellness Platform',
            'description' => 'MoodLink helps FEU Diliman look after its students. Track moods, '
                . 'spot students who need support early, and book counseling appointments — '
                . 'all in one place for the guidance office.',
            'robots' => 'index, follow',
        ],

        // Login page — public entry point for staff.
        '#^/login/?$#' => [
            'title' => 'Sign in — MoodLink FEU Diliman',
            'description' => 'Sign in to MoodLink to manage FEU Diliman student wellness, '
                . 'counseling appointments, and community posts.',
            'robots' => 'index, follow',
        ],

        // Private admin pages: clear titles, kept out of search (noindex default).
        '#^/dashboard#' => ['title' => 'Dashboard — MoodLink FEU Diliman'],
        '#^/student-accounts#' => ['title' => 'Student Accounts — MoodLink FEU Diliman'],
        '#^/post-management#' => ['title' => 'Post Management — MoodLink FEU Diliman'],
        '#^/reported-posts#' => ['title' => 'Reported Posts — MoodLink FEU Diliman'],
        '#^/appointments#' => ['title' => 'Appointments — MoodLink FEU Diliman'],
        '#^/reports#' => ['title' => 'Summary Reports — MoodLink FEU Diliman'],
        '#^/profile#' => ['title' => 'Profile Settings — MoodLink FEU Diliman'],
    ],
];
