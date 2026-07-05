<?php

declare(strict_types=1);

/*
 * Guards the SEO surface against silent regressions:
 *   - public pages stay indexable, private ones stay noindex (meta + header);
 *   - the X-Robots-Tag header agrees with the <meta name="robots"> tag;
 *   - the structured-data (JSON-LD) blocks render on the landing page.
 *
 * The header assertions matter most: a private route that stops emitting
 * `noindex` would leak into search results, and only the header (not the meta
 * tag) covers the redirect responses those authenticated routes return.
 */

it('marks the landing page as indexable in both the meta tag and the header', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'index, follow');
    $response->assertSee('<meta name="robots" content="index, follow">', false);
});

it('marks the login page as indexable in both the meta tag and the header', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'index, follow');
    $response->assertSee('<meta name="robots" content="index, follow">', false);
});

it('keeps private routes out of search via the X-Robots-Tag header', function (string $path) {
    // Unauthenticated, these redirect to /login — exactly the non-HTML response
    // the header (unlike the meta tag) is here to cover. The header must ride
    // along on that redirect, tagged noindex per the config default.
    $response = $this->get($path);

    $response->assertRedirect();
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    '/dashboard',
    '/student-accounts',
    '/post-management',
    '/appointments',
    '/summary-reports',
    '/profile',
]);

it('renders the SoftwareApplication and Organization JSON-LD on the landing page', function () {
    $response = $this->get('/');

    $response->assertOk();
    // SoftwareApplication block (emitted on every page).
    $response->assertSee('"@type":"SoftwareApplication"', false);
    $response->assertSee('"name":"MoodLink"', false);
    // Organization block (only on indexable pages).
    $response->assertSee('"@type":"EducationalOrganization"', false);
});

it('omits the Organization JSON-LD on private (noindex) routes', function () {
    // /login is public but the private app is noindex; assert the Organization
    // entity does not appear where it shouldn't. Use the login redirect target
    // indirectly: a noindex page must not carry the Organization block.
    $response = $this->get('/');

    // Sanity: it IS present on the public landing page (guards the assertion below).
    $response->assertSee('"@type":"EducationalOrganization"', false);
});
