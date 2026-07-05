<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Per-page SEO resolved from config/seo.php via the regex path finder. --}}
    <title inertia>{{ $seo['title'] ?? config('app.name', 'MoodLink') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <meta name="keywords" content="{{ $seo['keywords'] ?? '' }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'noindex, nofollow' }}">
    <meta name="author" content="FEU Diliman">
    <meta name="theme-color" content="#16a34a">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

    {{-- Open Graph (Facebook, LinkedIn, Messenger, etc.) --}}
    <meta property="og:site_name" content="MoodLink">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['title'] ?? config('app.name', 'MoodLink') }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    @if (!empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    {{-- Twitter / X cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? config('app.name', 'MoodLink') }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    @if (!empty($seo['image']))
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif

    {{-- Favicons --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Structured data: describes MoodLink to search engines --}}
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'MoodLink',
            'applicationCategory' => 'HealthApplication',
            'operatingSystem' => 'Web',
            'description' => $seo['description'] ?? '',
            'url' => config('app.url'),
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

    <!-- Scripts -->
    @routes(nonce: Vite::cspNonce())
    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
