<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasLoginTracking;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * @property string      $user_id
 * @property string      $role
 * @property string      $first_name
 * @property string      $last_name
 * @property string      $username
 * @property string|null $avatar
 * @property string|null $phone
 * @property string      $status
 * @property int         $failed_login_attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $full_name
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
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     *
     * @method \App\Models\User user()
     * Returns the related User model for this admin.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Accessors
     *
     * @method string fullName()
     * Returns the full name by combining first_name and last_name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /**
     * Presenters
     *
     * @method array profileSummary()
     * Read-only view of the admin (plus the related user's email) for the profile
     * settings page — shared by the ProfileCard and the personal-details form.
     * Name/phone are admin columns; email lives on the Supabase-managed user.
     * Writes are handled separately in ProfileController.
     *
     * @return array{firstName: string, lastName: string, email: string|null, phone: string|null, avatar: string|null, role: string, status: string}
     */
    public function profileSummary(): array
    {
        return [
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->user?->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'status' => $this->status,
        ];
    }

    /**
     * State checks
     *
     * @method bool isActive()
     * Checks if the admin status is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Finders
     *
     * @method static \App\Models\Admin|null findByEmail(string $email)
     * Finds an admin by the related user's email address.
     *
     * @method static \App\Models\Admin|null findByUserId(string $userId)
     * Finds an admin by user ID including soft-deleted records.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::whereHas('user', fn($q) => $q->where('email', $email))->first();
    }

    public static function findByUserId(string $userId): ?self
    {
        return static::withTrashed()->where('user_id', $userId)->first();
    }

}