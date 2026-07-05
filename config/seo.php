<?php

declare(strict_types=1);

return [

    // ── Site-wide defaults ────────────────────────────────────────────────
    'defaults' => [
        'title' => 'MoodLink — FEU Diliman Student Wellness',
        'description' => 'MoodLink is FEU Diliman\'s student wellness platform. Students use the '
            . 'mobile app to check in on their mood and engage with the community, while the '
            . 'guidance office uses a private web portal to track student wellbeing, review posts, '
            . 'and manage counseling appointments — so students who need support are spotted early.',
        'keywords' => 'MoodLink, FEU Diliman, student wellness, student mental health, '
            . 'mood tracking app, mood check-in, guidance office, counseling appointments, '
            . 'campus wellness, mHealth, at-risk student early warning',
        'image' => '/og-image.png',      // 1200×630 recommended; place in public/
        'robots' => 'noindex, nofollow', // private by default; public paths override below
        'type' => 'website',
    ],

    // ── Regex path → metadata (first match wins) ──────────────────────────
    'paths' => [

        // Landing page — the main public page (promotes the student mobile app).
        '#^/$#' => [
            'title' => 'MoodLink — FEU Diliman Student Wellness App',
            'description' => 'MoodLink is FEU Diliman\'s student wellness app. Students check in on '
                . 'their mood and engage with the community, while the guidance office spots '
                . 'students who need support early and manages counseling appointments. '
                . 'Learn about MoodLink and download the app.',
            'robots' => 'index, follow',
        ],

        // Login page — public entry point for guidance office staff (admin portal).
        '#^/login/?$#' => [
            'title' => 'Sign in — MoodLink FEU Diliman',
            'description' => 'Guidance office staff sign-in for the MoodLink admin portal — track '
                . 'FEU Diliman student wellbeing, manage counseling appointments, and moderate '
                . 'community posts.',
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
