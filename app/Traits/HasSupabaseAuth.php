<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\SupabaseAuthInterface;
use Carbon\Carbon;
use RuntimeException;
trait HasSupabaseAuth
{
    protected ?string $supabaseAccessToken = null;
    protected ?array $supabaseData = null;

    public function getSupabaseAuth(): SupabaseAuthInterface
    {
        return app(SupabaseAuthInterface::class);
    }

    public function updateSupabaseProfile(array $data): array
    {
        if ($this->supabaseAccessToken === null) {
            throw new RuntimeException('No access token available for Supabase API calls.');
        }

        $response = $this->getSupabaseAuth()->updateUser($this->supabaseAccessToken, $data);

        if (isset($response['id'])) {
            $this->supabaseData = $response;
            $this->save();
        }

        return $response;
    }

    public function changeSupabasePassword(string $newPassword): array
    {
        if ($this->supabaseAccessToken === null) {
            throw new RuntimeException('No access token available for Supabase API calls.');
        }

        return $this->getSupabaseAuth()->updatePassword($this->supabaseAccessToken, $newPassword);
    }

    public function getSupabaseUserId(): string
    {
        return $this->supabaseData['id'] ?? $this->id ?? '';
    }

    public function getSupabaseUserMetadata(): array
    {
        return $this->supabaseData['user_metadata'] ?? [];
    }

    public function getSupabaseAppMetadata(): array
    {
        return $this->supabaseData['app_metadata'] ?? [];
    }

    public function isEmailConfirmed(): bool
    {
        return !empty($this->supabaseData['email_confirmed_at']);
    }

    public function isPhoneConfirmed(): bool
    {
        return !empty($this->supabaseData['phone_confirmed_at']);
    }

    public function getSupabaseRole(): ?string
    {
        return $this->getSupabaseAppMetadata()['role'] ?? null;
    }

    public function getSupabaseProvider(): string
    {
        return $this->getSupabaseAppMetadata()['provider'] ?? 'email';
    }

    public function getLastSignInAt(): ?Carbon
    {
        return isset($this->supabaseData['last_sign_in_at'])
            ? Carbon::parse($this->supabaseData['last_sign_in_at'])
            : null;
    }

    public function getEmailConfirmedAt(): ?Carbon
    {
        return isset($this->supabaseData['email_confirmed_at'])
            ? Carbon::parse($this->supabaseData['email_confirmed_at'])
            : null;
    }

    public function isActive(): bool
    {
        $active = $this->getSupabaseAppMetadata()['active'] ?? true;
        return is_bool($active) ? $active : true;
    }

    public function isBanned(): bool
    {
        $banned = $this->getSupabaseAppMetadata()['banned'] ?? false;
        return is_bool($banned) ? $banned : false;
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return ($this->getSupabaseUserMetadata()['preferences'] ?? [])[$key] ?? $default;
    }

    public function updatePreferences(array $preferences): array
    {
        $meta = $this->getSupabaseUserMetadata();
        $meta['preferences'] = array_merge($meta['preferences'] ?? [], $preferences);

        return $this->updateSupabaseProfile(['data' => $meta]);
    }

    public function getSupabaseAvatar(): ?string
    {
        return $this->getSupabaseUserMetadata()['avatar_url'] ?? null;
    }

    public function getSupabaseFullName(): ?string
    {
        $meta = $this->getSupabaseUserMetadata();
        return $meta['full_name'] ?? $meta['name'] ?? null;
    }

    public function getLinkedIdentities(): array
    {
        return $this->supabaseData['identities'] ?? [];
    }
}
