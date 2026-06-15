<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Models\Post;
use App\Enums\YearLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a Student in the system.
 *
 * @property int $id
 * @property string|null $user_id Supabase auth user id (set on verification).
 * @property string $student_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $anonymous_name
 * @property StudentStatus $status
 * @property int $year_level
 * @property string $section
 * @property string|null $daily_result
 *
 * @property-read string $name
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 *
 * @method static Builder|Student verified()
 * @method static Builder|Student withStatus(StudentStatus $status)
 */
class Student extends Model
{
    protected $table = 'students';

    // The students table has no created_at / updated_at columns.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'anonymous_name',
        'status',
        'year_level',
        'section',
        'daily_result',
    ];

    protected $casts = [
        'status' => StudentStatus::class,
        'year_level' => 'integer',
    ];

    /** Full name composed from first and last name. */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /** @param Builder<Student> $query */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', StudentStatus::Verified->value);
    }

    /** @param Builder<Student> $query */
    public function scopeWithStatus(Builder $query, StudentStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /** @return HasMany<Post> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'student_id');
    }

    /** The Supabase auth user linked to this student (set on verification). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function toAdminRow(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_number,
            'name' => $this->name,
            'year_level' => YearLevel::tryFrom($this->year_level)?->label() ?? 'Not Set',
            'section' => $this->section,
            'verification_status' => $this->resolveVerificationStatus($this->status),
            'account_status' => $this->status->isActive() ? 'active' : 'suspended',
        ];
    }

    private function resolveVerificationStatus(StudentStatus $status): string
    {
        return match ($status) {
            StudentStatus::Suspended => StudentStatus::Verified->value,
            default => $status->value,
        };
    }

}
