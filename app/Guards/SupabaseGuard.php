<?php

declare(strict_types=1);

namespace App\Guards;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Contracts\SupabaseGuardInterface;
use App\Contracts\SupabaseUserProviderInterface;
use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Override;

use function count;

/**
 * Supabase authentication guard.
 *
 * Laravel guard that authenticates users using Supabase JWTs stored in the session.
 * Tokens are verified with Supabase on each request, with automatic refresh on expiry.
 *
 * Registered via Auth::extend('supabase', ...).
 */
class SupabaseGuard implements Guard, SupabaseGuardInterface
{
    use GuardHelpers;

    protected bool $loggedOut = false;
    protected ?Dispatcher $events = null;
    protected CookieJar $cookie;

    /**
     * Refreshed on every request by AppServiceProvider. 
     */
    protected Request $request;

    /**
     * @param string                $name     Guard name as registered in config/auth.php (e.g. "web").
     * @param UserProvider          $provider User provider responsible for loading the local User model.
     * @param Session               $session  Laravel session store.
     * @param SupabaseAuthInterface $supabase Supabase auth service for API calls.
     */
    public function __construct(
        protected readonly string $name,
        UserProvider $provider,
        protected readonly Session $session,
        protected readonly SupabaseAuthInterface $supabase,
    ) {
        $this->provider = $provider;
    }

    /**
     * Returns the authenticated user from the session without making any Supabase API calls.
     *
     * Token validation is handled separately by the EnsureTokenIsValid middleware on protected routes,
     * so this method is safe to run on every request (including public ones) as it must remain fast.
     */
    #[Override]
    public function user(): ?Authenticatable
    {
        if ($this->loggedOut) {
            return null;
        }

        // Already resolved for this request — skip DB lookup.
        if ($this->user !== null) {
            return $this->user;
        }

        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            return null;
        }

        // User ID was stored in the session by updateSession() during login.
        $id = $this->session->get($this->getName());

        if ($id === null) {
            return null;
        }

        $user = $this->provider->retrieveById($id);

        if ($user instanceof SupabaseAuthenticatable) {
            $user->setAccessToken($accessToken);

            // Attach cached Supabase user data stored during login/refresh.
            $supabaseData = $this->session->get('supabase_user');
            if ($supabaseData !== null) {
                $user->setSupabaseData($supabaseData);
            }
        }

        $this->user = $user;

        return $this->user;
    }

    /**
     * Check credentials without creating a session (stateless probe).
     * Verifies Supabase authentication without establishing a session.
     *
     * @param array{email?: string, password?: string} $credentials
     */
    #[Override]
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        try {
            $response = $this->supabase->signIn($credentials['email'], $credentials['password']);
            return isset($response['access_token']);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Attempts authentication using Supabase email/password login.
     *
     * Resolves a local user from the Supabase user ID, optionally creating
     * it if it does not exist, then stores Supabase tokens and initializes
     * the Laravel session.
     *
     * @param array{email?: string, password?: string} $credentials
     * @param bool $remember Not used; session lifetime is controlled by Supabase refresh tokens.
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        try {
            $response = $this->supabase->signIn($credentials['email'], $credentials['password']);
        } catch (Exception) {
            return false;
        }

        if (!isset($response['access_token'], $response['user'])) {
            return false;
        }

        // Try to find an existing local user row matching the Supabase UUID.
        $user = $this->provider->retrieveById($response['user']['id']);

        // First login — auto-create the local row from Supabase user data.
        if ($user === null && $this->provider instanceof SupabaseUserProviderInterface) {
            $user = $this->provider->createFromSupabase($response['user']);
        }

        if (!($user instanceof SupabaseAuthenticatable)) {
            return false;
        }

        $user->setSupabaseData($response['user']);
        $user->setAccessToken($response['access_token']);

        $this->login($user, $remember);

        if (isset($response['refresh_token'])) {
            $this->storeRefreshToken($response['refresh_token']);
        }

        $this->persistSupabaseSession($response['user'], $response['access_token']);

        return true;
    }

    /**
     * Register an authenticated user into the session.
     * Used after login or when a valid user is already available.
     */
    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken() !== null) {
            $this->storeAccessToken($user->getAccessToken());
        }

        $this->events?->dispatch(new Login($this->name, $user, $remember));
        $this->setUser($user);
    }

    /**
     * Sign the user out of both Supabase and the local session.
     *
     * The Supabase signOut call is best-effort — if it fails (e.g. token already
     * expired or network error) the local session is still destroyed so the user
     * cannot remain authenticated on this application.
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken() !== null) {
            try {
                $this->supabase->signOut($user->getAccessToken());
            } catch (Exception) {
                // best-effort: always clear local session even if Supabase call fails
            }
        }

        $this->clearUserDataFromStorage();
        $this->events?->dispatch(new Logout($this->name, $user));

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Silently exchange a stored refresh token for a new access token
     *
     * Called automatically by user when the current access token is rejected
     * by Supabase Returns true and updates the session on success false on
     * any failure missing refresh token network error Supabase rejection
     */
    public function refreshAccessToken(): bool
    {
        $refreshToken = $this->getRefreshToken();

        if ($refreshToken === null) {
            return false;
        }

        try {
            $response = $this->supabase->refreshToken($refreshToken);

            if (isset($response['access_token'])) {
                $this->storeAccessToken($response['access_token']);

                if (isset($response['refresh_token'])) {
                    $this->storeRefreshToken($response['refresh_token']);
                }

                $this->persistSupabaseSession($response['user'] ?? null, $response['access_token']);

                // Reset cache so user() re-resolves with the new token.
                $this->user = null;

                return true;
            }
        } catch (Exception) {
            // Refresh token is invalid or revoked — wipe local session entirely.
            $this->clearUserDataFromStorage();
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Session helpers
    // -------------------------------------------------------------------------

    /** 
     * Write the user's primary key to the session and rotate the session ID. 
     */
    protected function updateSession(mixed $id): void
    {
        $this->session->put($this->getName(), $id);
        $this->session->migrate(true);
    }

    /** 
     * Unique session key for this guard instance, scoped by class name. 
     */
    protected function getName(): string
    {
        return 'login_supabase_' . sha1(static::class);
    }

    /** 
     * Read the Supabase access token (JWT) from the session. 
     */
    protected function getAccessToken(): ?string
    {
        return $this->session->get('supabase_access_token');
    }

    /** 
     * Persist the Supabase access token to the session. 
     */
    protected function storeAccessToken(string $token): void
    {
        $this->session->put('supabase_access_token', $token);
    }

    /** 
     * Read the Supabase refresh token from the session. 
     */
    protected function getRefreshToken(): ?string
    {
        return $this->session->get('supabase_refresh_token');
    }

    /** 
     * Persist the Supabase refresh token to the session. 
     */
    protected function storeRefreshToken(string $token): void
    {
        $this->session->put('supabase_refresh_token', $token);
    }

    /**
     * Store raw Supabase user data and the token expiry timestamp in the session
     * The expiry is decoded from the JWT exp claim so token validity can be
     * checked without an extra API call included in EnsureTokenIsValid middleware
     */
    protected function persistSupabaseSession(?array $userData, ?string $accessToken): void
    {
        if ($userData !== null) {
            $this->session->put('supabase_user', $userData);
        }

        if ($accessToken !== null) {
            $this->session->put('supabase_session_expires_at', $this->expiryFromToken($accessToken));
        }
    }

    /**
     * Decode the `exp` claim from a JWT without verifying the signature.
     * Falls back to now + 1 hour if the token is malformed.
     */
    protected function expiryFromToken(string $jwt): int
    {
        $parts = explode('.', $jwt);

        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), strict: false) ?: '', true);

            if (isset($payload['exp'])) {
                return (int) $payload['exp'];
            }
        }

        return time() + 3600;
    }

    /** 
     * Remove all Supabase-related keys from the session. 
     */
    protected function clearUserDataFromStorage(): void
    {
        $this->session->remove($this->getName());
        $this->session->forget([
            'supabase_access_token',
            'supabase_refresh_token',
            'supabase_user',
            'supabase_session_expires_at',
        ]);
    }

    // -------------------------------------------------------------------------
    // Injected by AppServiceProvider after construction
    // -------------------------------------------------------------------------

    /** @param CookieJar $cookie Laravel cookie jar (available for future cookie-based features). */
    public function setCookieJar(CookieJar $cookie): void
    {
        $this->cookie = $cookie;
    }

    /** @param Dispatcher $events Laravel event dispatcher for Login/Logout events. */
    public function setDispatcher(Dispatcher $events): void
    {
        $this->events = $events;
    }

    /** Refreshed on every request so the guard always has the current HTTP request. */
    public function setRequest(Request $request): static
    {
        $this->request = $request;
        return $this;
    }
}
