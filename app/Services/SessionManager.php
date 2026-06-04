<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseUserProviderInterface;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class SessionManager
{
    private const string SESSION_USER = 'supabase_user';
    private const string SESSION_ACCESS_TOKEN = 'supabase_access_token';
    private const string SESSION_REFRESH_TOKEN = 'supabase_refresh_token';
    private const string SESSION_EXPIRES_AT = 'supabase_session_expires_at';

    private const DEFAULT_SESSION_LIFETIME = 3600;

    public function __construct(
        private readonly Store $session,
        private readonly SupabaseAuth $supabase,
    ) {
    }

    /**
     * Store Supabase user session data.
     */
    public function storeUserSession(
        array $userData,
        string $accessToken,
        string|null $refreshToken = null
    ): void {
        $this->session->put(self::SESSION_USER, $userData);
        $this->session->put(self::SESSION_ACCESS_TOKEN, $accessToken);

        if ($refreshToken !== null) {
            $this->session->put(self::SESSION_REFRESH_TOKEN, $refreshToken);
        }

        $this->session->put(
            self::SESSION_EXPIRES_AT,
            time() + self::DEFAULT_SESSION_LIFETIME
        );

        $this->session->regenerate();
    }

    /**
     * Get authenticated user from session.
     */
    public function getUserFromSession(): ?array
    {
        return $this->session->get(self::SESSION_USER);
    }

    public function getAccessToken(): ?string
    {
        return $this->session->get(self::SESSION_ACCESS_TOKEN);
    }

    public function getRefreshToken(): ?string
    {
        return $this->session->get(self::SESSION_REFRESH_TOKEN);
    }

    /**
     * Check if session is expired.
     */
    public function isSessionExpired(): bool
    {
        $expiresAt = $this->session->get(self::SESSION_EXPIRES_AT);

        return $expiresAt === null || time() >= $expiresAt;
    }

    /**
     * Refresh Supabase session using refresh token.
     */
    public function refreshSession(): bool
    {
        $refreshToken = $this->getRefreshToken();

        if ($refreshToken === null) {
            return false;
        }

        try {
            $response = $this->supabase->refreshToken($refreshToken);

            if (isset($response['access_token'], $response['user'])) {
                $this->storeUserSession(
                    userData: $response['user'],
                    accessToken: $response['access_token'],
                    refreshToken: $response['refresh_token'] ?? $refreshToken,
                );

                return true;
            }
        } catch (Throwable) {
            $this->clearSession();
        }

        return false;
    }

    /**
     * Clear all Supabase session data.
     */
    public function clearSession(): void
    {
        $this->session->forget([
            self::SESSION_USER,
            self::SESSION_ACCESS_TOKEN,
            self::SESSION_REFRESH_TOKEN,
            self::SESSION_EXPIRES_AT,
        ]);

        $this->session->regenerate();
    }

    /**
     * Extend session lifetime.
     */
    public function extendSession(int $seconds = self::DEFAULT_SESSION_LIFETIME): void
    {
        $this->session->put(
            self::SESSION_EXPIRES_AT,
            time() + $seconds
        );
    }

    /**
     * Validate current session with Supabase.
     */
    public function validateSession(): bool
    {
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            return false;
        }

        if ($this->isSessionExpired()) {
            return $this->refreshSession();
        }

        $validation = $this->supabase->verifyToken($accessToken);

        return $validation['valid'] ? true : $this->refreshSession();
    }

    /**
     * Sync Supabase session with Laravel Auth guard.
     */
    public function syncWithAuthGuard(): bool
    {
        if (!$this->validateSession()) {
            Auth::logout();
            return false;
        }

        $userData = $this->getUserFromSession();
        $accessToken = $this->getAccessToken();

        if ($userData === null || $accessToken === null) {
            return false;
        }

        $provider = Auth::createUserProvider('users');
        $user = $provider?->retrieveById($userData['id']);

        if ($user === null && $provider instanceof SupabaseUserProviderInterface) {
            $user = $provider->createFromSupabase($userData);
        }

        if ($user instanceof SupabaseAuthenticatable) {
            $user->setSupabaseData($userData);
            $user->setAccessToken($accessToken);

            Auth::setUser($user);

            return true;
        }

        return false;
    }
}