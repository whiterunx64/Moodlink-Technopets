<?php

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @extends Model
 *
 * @property int $id
 * @property int $student_id
 * @property string|null $content
 * @property PostMood|null $mood
 * @property \Illuminate\Support\Carbon $datetime
 * @property PostStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Student|null $student
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Student, \App\Models\Post> student()
 */
class Post extends Model
{
    protected $table = 'posts';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'student_id',
        'content',
        'mood',
        'datetime',
        'status',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'mood'     => PostMood::class,
        'status'   => PostStatus::class,
    ];

    /** @param Builder<Post> $query */
    public function scopeFromVerifiedStudents(Builder $query): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->verified());
    }

    /** @return BelongsTo<Student, Post> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
