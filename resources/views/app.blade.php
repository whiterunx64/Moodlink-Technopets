<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $seoDefaults = config('seo.defaults', []);
        $seoTitle = $seo['title'] ?? $seoDefaults['title'] ?? config('app.name', 'MoodLink');
        $seoDescription = $seo['description'] ?? $seoDefaults['description'] ?? '';
        $seoKeywords = $seo['keywords'] ?? $seoDefaults['keywords'] ?? '';
        $seoRobots = $seo['robots'] ?? $seoDefaults['robots'] ?? 'noindex, nofollow';
        $seoType = $seo['type'] ?? $seoDefaults['type'] ?? 'website';
        $seoCanonical = $seo['canonical'] ?? url()->current();
    @endphp
    <title inertia>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="author" content="FEU Diliman">
    <meta name="theme-color" content="#16a34a">
    <link rel="canonical" href="{{ $seoCanonical }}">

    {{-- Only advertise a social image if the file actually exists, so we never
         emit a tag pointing at a missing asset (which crawlers fetch and 404). --}}
    @php $hasOgImage = file_exists(public_path('og-image.png')); @endphp

    {{-- Open Graph (Facebook, LinkedIn, Messenger, etc.) --}}
    <meta property="og:site_name" content="MoodLink">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    @if ($hasOgImage)
        <meta property="og:image" content="{{ url('/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    {{-- Twitter / X cards --}}
    <meta name="twitter:card" content="{{ $hasOgImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if ($hasOgImage)
        <meta name="twitter:image" content="{{ url('/og-image.png') }}">
    @endif

    {{-- AI answer engines (llms.txt convention) — a plain-text summary of what
         MoodLink is, for generative search / LLM crawlers. --}}
    <link rel="llms" type="text/markdown" href="/llms.txt" title="MoodLink llms.txt">

    {{-- Favicons --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @if (file_exists(public_path('apple-touch-icon.png')))
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @endif

    {{-- Structured data: describes MoodLink to search engines --}}
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'MoodLink',
            'applicationCategory' => 'HealthApplication',
            'operatingSystem' => 'Web',
            'description' => $seoDescription,
            'url' => config('app.url'),
            'inLanguage' => 'en',
            'isAccessibleForFree' => true,
            'featureList' => [
                'Student mood check-ins',
                'Community engagement',
                'Early identification of at-risk students',
                'Counseling appointment scheduling',
                'Community post moderation',
            ],
            'audience' => [
                '@type' => 'EducationalAudience',
                'educationalRole' => 'student',
                'audienceType' => 'FEU Diliman students and guidance office staff',
            ],
            'publisher' => [
                '@type' => 'CollegeOrUniversity',
                'name' => 'Far Eastern University – Diliman',
                'alternateName' => 'FEU Diliman',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'PHP',
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>

    {{-- Organization entity — only on indexable (public) pages. Lets search/AI
         engines treat FEU Diliman as a first-class entity behind MoodLink,
         rather than just a nested publisher of the app. --}}
    @if (str_contains($seoRobots, 'index') && ! str_contains($seoRobots, 'noindex'))
        @php
            $organizationData = [
                '@context' => 'https://schema.org',
                '@type' => 'EducationalOrganization',
                'name' => 'Far Eastern University – Diliman',
                'alternateName' => 'FEU Diliman',
                'url' => 'https://feudiliman.edu.ph',
                'department' => [
                    '@type' => 'Organization',
                    'name' => 'FEU Diliman Guidance Office',
                ],
            ];
        @endphp
        <script type="application/ld+json">
            {!! json_encode($organizationData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endif

    <!-- Scripts -->
    @routes(group: auth()->check() ? null : 'public', nonce: Vite::cspNonce())
    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
