<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Session\Session;

class SupabasePersistentStorage
{
    private const string KEY_ACCESS_TOKEN = 'supabase_access_token';
    private const string KEY_REFRESH_TOKEN = 'supabase_refresh_token';
    private const string KEY_USER_DATA = 'supabase_user';
    private const string KEY_EXPIRES_AT = 'supabase_session_expires_at';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    /**
     * User Identity
     */

    public function getUserId(string $guardKey): mixed
    {
        return $this->session->get($guardKey);
    }

    public function storeUserId(string $guardKey, mixed $id): void
    {
        $this->session->put($guardKey, $id);
        $this->session->migrate(true);
    }

    /**
     * Token Storage
     */

    public function getAccessToken(): ?string
    {
        return $this->session->get(self::KEY_ACCESS_TOKEN);
    }

    public function storeAccessToken(string $token): void
    {
        $this->session->put(self::KEY_ACCESS_TOKEN, $token);
    }

    public function getRefreshToken(): ?string
    {
        return $this->session->get(self::KEY_REFRESH_TOKEN);
    }

    public function storeRefreshToken(string $token): void
    {
        $this->session->put(self::KEY_REFRESH_TOKEN, $token);
    }

    /**
     * User Data & Session Expiry
     */

    public function getUserData(): ?array
    {
        return $this->session->get(self::KEY_USER_DATA);
    }

    public function persist(?array $userData, ?int $expiresAt): void
    {
        if ($userData !== null) {
            $this->session->put(self::KEY_USER_DATA, $userData);
        }

        if ($expiresAt !== null) {
            $this->session->put(self::KEY_EXPIRES_AT, $expiresAt);
        }
    }

    /**
     * Session Cleanup
     */

    public function flush(string $guardKey): void
    {
        $this->session->remove($guardKey);

        $this->session->forget([
            self::KEY_ACCESS_TOKEN,
            self::KEY_REFRESH_TOKEN,
            self::KEY_USER_DATA,
            self::KEY_EXPIRES_AT,
        ]);
    }
}