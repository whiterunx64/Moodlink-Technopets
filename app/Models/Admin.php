<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminUserStatus;
use App\Traits\HasLoginTracking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * @property string $user_id
 * @property string $role
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property string|null $avatar
 * @property string|null $phone
 * @property AdminUserStatus $status
 * @property int $failed_login_attempts
 * @property Carbon|null $locked_until
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read \App\Models\User $linkedSupabaseUser
 *
 * @mixin HasLoginTracking
 */

class Admin extends Model
{
    use SoftDeletes, HasLoginTracking;

    protected $fillable = [
        'user_id',
        'role',
        'first_name',
        'last_name',
        'username',
        'avatar',
        'phone',
        'status',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'last_login_ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdminUserStatus::class,
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function linkedSupabaseUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function profileSummary(): array
    {
        return [
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'status' => $this->status?->value,
        ];
    }

    public function savePersonalDetails(array $details): bool
    {
        return $this->update([
            'first_name' => $details['firstName'],
            'last_name' => $details['lastName'],
            'phone' => $details['phone'] ?? null,
            'avatar' => $details['avatar'] ?? $this->avatar,
        ]);
    }

    public function saveAvatarUrl(string $url): bool
    {
        return $this->update(['avatar' => $url]);
    }

    public function deactivateAndDelete(): void
    {
        $this->update(['status' => AdminUserStatus::Inactive]);
        $this->delete();
    }

    public static function findByEmail(string $email): ?self
    {
        return static::whereHas(
            'linkedSupabaseUser',
            fn($query) => $query->where('email', $email)
        )->first();
    }

    public static function findByUserId(string $userId): ?self
    {
        return static::withTrashed()
            ->where('user_id', $userId)
            ->first();
    }

    public static function isAdministratorRole(string $userId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('role', 'Administrator')
            ->exists();
    }
}