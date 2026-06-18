<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SupabaseAuthenticatable;
use App\Traits\HasSupabaseAuth;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string|null $email
 * @property array<string, mixed>|null $raw_user_meta_data
 * @property array<string, mixed>|null $raw_app_meta_data
 *
 * @property \Illuminate\Support\Carbon|null $email_confirmed_at
 * @property \Illuminate\Support\Carbon|null $phone_confirmed_at
 * @property \Illuminate\Support\Carbon|null $last_sign_in_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property \Illuminate\Support\Carbon|null $invited_at
 * @property \Illuminate\Support\Carbon|null $confirmation_sent_at
 * @property \Illuminate\Support\Carbon|null $recovery_sent_at
 * @property \Illuminate\Support\Carbon|null $email_change_sent_at
 * @property \Illuminate\Support\Carbon|null $phone_change_sent_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $reauthentication_sent_at
 *
 * @property bool $is_super_admin
 * @property bool $is_sso_user
 * @property bool $is_anonymous
 *
 * @property int|null $email_change_confirm_status
 *
 * @property-read Carbon|null $banned_until
 */

class User extends Authenticatable implements SupabaseAuthenticatable
{
    use Notifiable, HasSupabaseAuth;

    // Supabase-managed table — Laravel never migrates or writes to this schema.
    protected $table = 'auth.users';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = ['*'];

    // Hide all token/password/internal columns from serialization.
    protected $hidden = [
        'encrypted_password',
        'confirmation_token',
        'confirmation_sent_at',
        'recovery_token',
        'recovery_sent_at',
        'email_change_token_new',
        'email_change_token_current',
        'email_change_sent_at',
        'phone_change_token',
        'phone_change_sent_at',
        'reauthentication_token',
        'reauthentication_sent_at',
        'instance_id',
        'is_super_admin',
        'aud',
    ];

    protected function casts(): array
    {
        return [
            // JSON columns
            'raw_user_meta_data' => 'array',
            'raw_app_meta_data' => 'array',

            // Datetime columns matching auth.users schema
            'invited_at' => 'datetime',
            'email_confirmed_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'recovery_sent_at' => 'datetime',
            'email_change_sent_at' => 'datetime',
            'last_sign_in_at' => 'datetime',
            'phone_confirmed_at' => 'datetime',
            'phone_change_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reauthentication_sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',

            // Boolean columns
            'is_super_admin' => 'boolean',
            'is_sso_user' => 'boolean',
            'is_anonymous' => 'boolean',

            // Integer
            'email_change_confirm_status' => 'integer',
        ];
    }

    // banned_until can return "none" from Supabase — handle safely via accessor.
    public function getBannedUntilAttribute(?string $value): ?Carbon
    {
        if (empty($value) || $value === 'none') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    /* ---- SupabaseAuthenticatable ---- */

    #[\Override]
    public function setSupabaseData(array $data): static
    {
        $this->supabaseData = $data;
        return $this;
    }

    #[\Override]
    public function setAccessToken(string $token): static
    {
        $this->supabaseAccessToken = $token;
        return $this;
    }

    #[\Override]
    public function getAccessToken(): ?string
    {
        return $this->supabaseAccessToken;
    }

    // Fall back to Eloquent attributes when supabaseData is not hydrated from the API.
    public function getSupabaseUserMetadata(): array
    {
        return $this->supabaseData['user_metadata'] ?? $this->raw_user_meta_data ?? [];
    }

    public function getSupabaseAppMetadata(): array
    {
        return $this->supabaseData['app_metadata'] ?? $this->raw_app_meta_data ?? [];
    }

    public function isEmailConfirmed(): bool
    {
        return !empty($this->supabaseData['email_confirmed_at']) || !empty($this->email_confirmed_at);
    }

    public function isPhoneConfirmed(): bool
    {
        return !empty($this->supabaseData['phone_confirmed_at']) || !empty($this->phone_confirmed_at);
    }

    public function getLastSignInAt(): ?Carbon
    {
        if (isset($this->supabaseData['last_sign_in_at'])) {
            return Carbon::parse($this->supabaseData['last_sign_in_at']);
        }
        return $this->last_sign_in_at instanceof Carbon ? $this->last_sign_in_at : null;
    }

    public function isBanned(): bool
    {
        if (isset($this->supabaseData['app_metadata']['banned'])) {
            return (bool) $this->supabaseData['app_metadata']['banned'];
        }
        $until = $this->getBannedUntilAttribute($this->attributes['banned_until'] ?? null);
        return $until !== null && $until->isFuture();
    }

    public function getMetadata(): array
    {
        return $this->getSupabaseUserMetadata();
    }

    public function getAppMetadata(): array
    {
        return $this->getSupabaseAppMetadata();
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailConfirmed();
    }
}