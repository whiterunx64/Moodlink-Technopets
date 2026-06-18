<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Traits\HasAdminPagination;
use App\Traits\HasDateTimeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property int $id
 * @property int $student_id
 * @property string|null $content
 * @property \App\Enums\PostMood|null $mood
 * @property \App\Enums\PostStatus $status
 * @property \Illuminate\Support\Carbon $datetime
 *
 * @property-read \App\Models\Student|null $student
 *
 * @method static Builder|Post fromVerifiedStudents()
 * @method static Builder|Post withPostStatus(string $status)
 * @method static Builder|Post fromStudentSection(string $section)
 * @method static Builder|Post withPostMood(string $mood)
 * @method static Builder|Post sortedByDateDirection(string $direction)
 *
 * @mixin HasAdminPagination
 * @mixin HasDateTimeDisplay
 */

class Post extends Model
{
    use HasAdminPagination, HasDateTimeDisplay;

    protected $table = 'posts';
    public const UPDATED_AT = null;
    public const CREATED_AT = null;
    public const int ADMIN_PAGE_SIZE = 15;

    protected $fillable = [
        'student_id',
        'content',
        'mood',
        'datetime',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
            'mood' => PostMood::class,
            'status' => PostStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Student, Post>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeFromVerifiedStudents(Builder $query): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->verified());
    }

    public function scopeWithPostStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFromStudentSection(Builder $query, string $section): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->where('section', $section));
    }

    public function scopeWithPostMood(Builder $query, string $mood): Builder
    {
        return $query->where('mood', $mood);
    }

    public function scopeSortedByDateDirection(Builder $query, string $direction): Builder
    {
        if ($direction === 'oldest') {
            return $query->orderBy('datetime');
        }

        return $query->orderByDesc('datetime');
    }

    public static function queryVerifiedPostsWithFilters(array $filters): Builder
    {
        return static::query()
            ->with('student')
            ->fromVerifiedStudents()
            ->when(
                $filters['status'] ?? null,
                fn(Builder $q, string $status) => $q->withPostStatus($status)
            )
            ->when(
                $filters['section'] ?? null,
                fn(Builder $q, string $section) => $q->fromStudentSection($section)
            )
            ->when(
                $filters['mood'] ?? null,
                fn(Builder $q, string $mood) => $q->withPostMood($mood)
            )
            ->sortedByDateDirection($filters['sort'] ?? 'latest');
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        return static::paginateForAdmin(static::queryVerifiedPostsWithFilters($filters))
            ->through(fn(Post $post): array => [
                'id' => $post->id,
                'content' => $post->content,
                'mood' => $post->mood?->value,
                'status' => $post->status?->value,
                'date' => $post->displayDate(),
                'time' => $post->displayTime(),
                'section' => $post->student?->section ?? '',
                'anonymous_name' => $post->student?->anonymous_name,
            ]);
    }
}