<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model
{
    use SoftDeletes;

    protected $table = 'admins';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'role',
        'last_name',
        'first_name',
        'username',
        'user_id',
        'avatar',
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
            'locked_until'          => 'datetime',
            'last_login_at'         => 'datetime',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
            'deleted_at'            => 'datetime',
        ];
    }

    // admins.user_id → auth.users.id (nullable — admin may exist without a Supabase account)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->locked_until instanceof Carbon && $this->locked_until->isFuture();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
