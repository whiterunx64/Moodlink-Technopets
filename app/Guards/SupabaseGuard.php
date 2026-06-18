<?php

declare(strict_types=1);

namespace App\Guards;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseAuthInterface;
use App\Contracts\SupabaseGuardInterface;
use App\Contracts\SupabaseUserProviderInterface;
use App\Models\Admin;
use App\Services\SupabaseSessionStore;
use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Override;

/**
 * Laravel guard that authenticates users using Supabase JWTs stored in the session.
 * Tokens are verified with Supabase on each request, with automatic refresh on expiry.
 *
 * Registered via Auth::extend('supabase', ...).
 */
class SupabaseGuard implements Guard, SupabaseGuardInterface
{
    use GuardHelpers;

    private const string FIELD_EMAIL = 'email';
    private const string FIELD_PASSWORD = 'password';
    private const string FIELD_ACCESS_TOKEN = 'access_token';
    private const string FIELD_REFRESH_TOKEN = 'refresh_token';
    private const string FIELD_USER = 'user';

    protected bool $loggedOut = false;
    protected ?Dispatcher $events = null;
    protected CookieJar $cookie;
    protected Request $request;

    /**
     * @param string                    $name           Guard name as registered in config/auth.php.
     * @param UserProvider              $provider       Loads the local User model.
     * @param SupabaseSessionStore    $storage Owns all session read/write operations.
     * @param SupabaseAuthInterface     $supabase       Supabase auth service for API calls.
     */

    public function __construct(
        protected readonly string $name,
        UserProvider $provider,
        protected readonly SupabaseSessionStore $storage,
        protected readonly SupabaseAuthInterface $supabase,
    ) {
        $this->provider = $provider;
    }

    /**
     * Returns session user without Supabase calls.
     * Token validation is handled by middleware.
     */
    #[Override]
    public function user(): ?Authenticatable
    {
        if ($this->loggedOut || $this->user !== null) {
            return $this->user;
        }

        $accessToken = $this->storage->getAccessToken();
        $id = $this->storage->getUserId($this->getName());

        if ($accessToken === null || $id === null) {
            return null;
        }

        $user = $this->provider->retrieveById($id);

        if ($user instanceof SupabaseAuthenticatable) {
            $user->setAccessToken($accessToken);

            if (($data = $this->storage->getUserData()) !== null) {
                $user->setSupabaseData($data);
            }
        }

        return $this->user = $user;
    }

    /**
     * Stateless credential probe.
     *
     * @param array{email?: string, password?: string} $credentials
     */
    #[Override]
    public function validate(array $credentials = []): bool
    {
        if (!$this->hasRequiredCredentials($credentials)) {
            return false;
        }

        return $this->attemptSupabaseSignIn(
            $credentials[self::FIELD_EMAIL],
            $credentials[self::FIELD_PASSWORD]
        );
    }

    /**
     * Authenticate a user via Supabase and establish a Laravel session.
     *
     * @param array{email?: string, password?: string} $credentials
     */
    public function attempt(array $credentials = []): bool
    {
        if (!$this->hasRequiredCredentials($credentials)) {
            return false;
        }

        $admin = Admin::findByEmail($credentials[self::FIELD_EMAIL]);
        $admin?->abortIfLocked();

        try {
            $response = $this->supabase->signIn($credentials[self::FIELD_EMAIL], $credentials[self::FIELD_PASSWORD]);
        } catch (Exception) {
            $admin?->recordFailedAttempt();
            return false;
        }

        if (!isset($response[self::FIELD_ACCESS_TOKEN], $response[self::FIELD_USER])) {
            $admin?->recordFailedAttempt();
            return false;
        }

        $user = $this->resolveUser($response[self::FIELD_USER]);

        if (!($user instanceof SupabaseAuthenticatable)) {
            return false;
        }

        $user->setSupabaseData($response[self::FIELD_USER]);
        $user->setAccessToken($response[self::FIELD_ACCESS_TOKEN]);

        Admin::findByUserId($response[self::FIELD_USER]['id'])?->activateAfterLogin($this->request->ip());

        $this->login($user);
        $this->persistSession($response);

        return true;
    }

    /** 
     * Registers an authenticated user into the session and fires the Login event. 
     */
    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->storage->storeUserId($this->getName(), $user->getAuthIdentifier());

        if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken() !== null) {
            $this->storage->storeAccessToken($user->getAccessToken());
        }

        $this->events?->dispatch(new Login($this->name, $user, $remember));
        $this->setUser($user);
    }

    /**
     * Sign the user out of Supabase and clear the local session.
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($user instanceof SupabaseAuthenticatable && $user->getAccessToken() !== null) {
            try {
                $this->supabase->signOut($user->getAccessToken());
            } catch (Exception) {
            }
        }

        $this->storage->flush($this->getName());
        $this->events?->dispatch(new Logout($this->name, $user));

        $this->user = null;
        $this->loggedOut = true;
    }

    /** 
     * Exchange the refresh token for a new access token and update the session. 
     */
    public function refreshAccessToken(): bool
    {
        $refreshToken = $this->storage->getRefreshToken();

        if ($refreshToken === null) {
            return false;
        }

        try {
            $response = $this->supabase->refreshToken($refreshToken);
        } catch (Exception) {
            $this->storage->flush($this->getName()); // Remove session data after refresh token is revoked
            return false;
        }

        if (!isset($response[self::FIELD_ACCESS_TOKEN])) {
            return false;
        }

        $this->storage->storeAccessToken($response[self::FIELD_ACCESS_TOKEN]);
        $this->persistSession($response);

        $this->user = null; // Clear cached user.

        return true;
    }

    /** Unique session key for this guard instance, scoped by class name. */
    protected function getName(): string
    {
        return 'login_supabase_' . sha1(static::class);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------
    private function hasRequiredCredentials(array $credentials): bool
    {
        return !empty($credentials[self::FIELD_EMAIL]) && !empty($credentials[self::FIELD_PASSWORD]);
    }

    private function resolveUser(array $supabaseUser): ?SupabaseAuthenticatable
    {
        $user = $this->provider->retrieveById($supabaseUser['id']);

        if (!$user && $this->provider instanceof SupabaseUserProviderInterface) {
            $user = $this->provider->createFromSupabase($supabaseUser);
        }

        if (!$user instanceof SupabaseAuthenticatable) {
            return null;
        }

        return $user;
    }

    private function persistSession(array $response): void
    {
        if (isset($response[self::FIELD_REFRESH_TOKEN])) {
            $this->storage->storeRefreshToken($response[self::FIELD_REFRESH_TOKEN]);
        }

        $this->storage->persist(
            $response[self::FIELD_USER] ?? null,
            isset($response['expires_at']) ? (int) $response['expires_at'] : null,
        );
    }

    private function attemptSupabaseSignIn(string $email, string $password): bool
    {
        try {
            $response = $this->supabase->signIn($email, $password);

            return isset($response[self::FIELD_ACCESS_TOKEN]);
        } catch (Exception) {
            return false;
        }
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