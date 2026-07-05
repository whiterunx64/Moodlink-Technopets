<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Resolves per-page SEO metadata by matching the request path against the
 * regex patterns configured in config/seo.php (the "regex finder path").
 */
final class Seo
{
    /**
     * Build the merged SEO metadata for a given request path.
     *
     * @param  string  $path  Request path including leading slash, no query string (e.g. "/dashboard").
     * @return array{title:string,description:string,keywords:string,image:string,robots:string,type:string,canonical:string}
     */
    public static function forPath(string $path): array
    {
        /** @var array<string,mixed> $defaults */
        $defaults = config('seo.defaults', []);

        /** @var array<string,array<string,mixed>> $paths */
        $paths = config('seo.paths', []);

        $normalized = self::normalize($path);

        $overrides = [];
        foreach ($paths as $pattern => $meta) {
            if (self::matches($pattern, $normalized)) {
                $overrides = $meta;
                break; // first match wins
            }
        }

        $meta = [...$defaults, ...$overrides];

        // Absolute URLs for canonical + social image, which crawlers require.
        $meta['canonical'] = URL::to($normalized === '/' ? '/' : $normalized);
        $meta['image'] = self::absoluteUrl($meta['image'] ?? '');

        return $meta;
    }

    /**
     * Safely test a configured pattern against a path. An invalid pattern is
     * skipped rather than throwing, so a bad config entry can't 500 the page.
     */
    private static function matches(string $pattern, string $path): bool
    {
        return @preg_match($pattern, $path) === 1;
    }

    /**
     * Reduce a raw path to a stable form: leading slash, no query/fragment,
     * no trailing slash (except root).
     */
    private static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function absoluteUrl(string $url): string
    {
        if ($url === '' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return URL::to($url);
    }
}
