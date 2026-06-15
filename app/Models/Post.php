<?php

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Traits\HasPaginatedList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                        $id
 * @property int                        $student_id
 * @property string|null                $content
 * @property PostMood|null              $mood
 * @property PostStatus                 $status
 * @property \Illuminate\Support\Carbon $datetime
 *
 * @property-read Student|null $student
 *
 * @method static Builder|Post fromVerifiedStudents()
 * @method static Builder|Post withPostStatus(string $status)
 * @method static Builder|Post fromStudentSection(string $section)
 * @method static Builder|Post withPostMood(string $mood)
 * @method static Builder|Post sortedByDateDirection(string $direction)
 */
class Post extends Model
{
    use HasPaginatedList;
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

    protected $casts = [
        'datetime' => 'datetime',
        'mood' => PostMood::class,
        'status' => PostStatus::class,
    ];

    /**
     * The student who authored this post.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * only include posts from verified students.
     */
    public function scopeFromVerifiedStudents(Builder $query): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->verified());
    }

    /**
     * filter posts by their status value.
     */
    public function scopeWithPostStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * filter posts to a specific student section.
     */
    public function scopeFromStudentSection(Builder $query, string $section): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->where('section', $section));
    }

    /**
     * filter posts by mood value.
     */
    public function scopeWithPostMood(Builder $query, string $mood): Builder
    {
        return $query->where('mood', $mood);
    }

    /**
     * order posts by datetime — 'oldest' ascending, anything else descending.
     */
    public function scopeSortedByDateDirection(Builder $query, string $direction): Builder
    {
        return $direction === 'oldest'
            ? $query->orderBy('datetime')
            : $query->orderByDesc('datetime');
    }

    /**
     * Build a base query for verified student posts with all given filters and sort applied.
     */
    public static function queryVerifiedPostsWithFilters(array $filters): Builder
    {
        $status = $filters['status'] ?? null;
        $section = $filters['section'] ?? null;
        $mood = $filters['mood'] ?? null;
        $direction = $filters['sort'] ?? 'latest';

        return static::query()
            ->with('student')
            ->fromVerifiedStudents()
            ->when($status !== null, fn(Builder $q) => $q->withPostStatus($status))
            ->when($section !== null, fn(Builder $q) => $q->fromStudentSection($section))
            ->when($mood !== null, fn(Builder $q) => $q->withPostMood($mood))
            ->sortedByDateDirection($direction);
    }

    protected static function filteredQuery(array $filters): Builder
    {
        return static::queryVerifiedPostsWithFilters($filters);
    }

    public function toListRow(): array
    {
        $localDatetime = $this->datetime->setTimezone('Asia/Manila');

        return [
            'id' => $this->id,
            'content' => $this->content,
            'mood' => $this->mood?->value,
            'status' => $this->status?->value,
            'date' => $localDatetime->format('Y-m-d'),
            'time' => $localDatetime->format('h:i A'),
            'section' => $this->student?->section ?? '',
            'anonymous_name' => $this->student?->anonymous_name,
        ];
    }
}
