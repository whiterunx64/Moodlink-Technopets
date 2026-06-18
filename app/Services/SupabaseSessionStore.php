<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Session\Session;

class SupabaseSessionStore
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function getUserId(string $guardKey): mixed
    {
        return $this->session->get($guardKey);
    }

    public function storeUserId(string $guardKey, mixed $id): void
    {
        $this->session->put($guardKey, $id);
        $this->session->migrate(true); // regenerate session ID to prevent fixation
    }

    public function getAccessToken(): ?string
    {
        return $this->session->get('supabase_access_token');
    }

    public function storeAccessToken(string $token): void
    {
        $this->session->put('supabase_access_token', $token);
    }

    public function getRefreshToken(): ?string
    {
        return $this->session->get('supabase_refresh_token');
    }

    public function storeRefreshToken(string $token): void
    {
        $this->session->put('supabase_refresh_token', $token);
    }


    public function getUserData(): ?array
    {
        return $this->session->get('supabase_user');
    }

    /**
     * @param int|null $expiresAt Unix timestamp
     */
    public function persist(?array $userData, ?int $expiresAt): void
    {
        if ($userData !== null) {
            $this->session->put('supabase_user', $userData);
        }

        if ($expiresAt !== null) {
            $this->session->put('supabase_session_expires_at', $expiresAt);
        }
    }

    public function flush(string $guardKey): void
    {
        $this->session->remove($guardKey);

        $this->session->forget([
            'supabase_access_token',
            'supabase_refresh_token',
            'supabase_user',
            'supabase_session_expires_at',
        ]);
    }
}