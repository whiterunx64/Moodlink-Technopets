<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SupabaseAuthenticatable;
use App\Traits\HasSupabaseAuth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    // Hide all token/password columns from serialization.
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
    ];

    protected function casts(): array
    {
        return [
            // JSON columns
            'raw_user_meta_data' => 'array',
            'raw_app_meta_data' => 'array',

            // Datetime columns matching auth.users schema
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

    // students.id = auth.users.id (same UUID — standard Supabase 1-to-1 pattern).
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id', 'id');
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

    /* ---- auth.users has no remember_token column ---- */
    public function getRememberToken(): null
    {
        return null;
    }
    public function setRememberToken($value): void
    {
    }
    public function getRememberTokenName(): null
    {
        return null;
    }
}