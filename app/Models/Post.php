<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Traits\HasAdminPagination;
use App\Traits\HasDateTimeDisplay;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use function in_array;

/**
 * @property int $id
 * @property int $student_id
 * @property string|null $content
 * @property PostMood|null $mood
 * @property PostStatus $status
 * @property Carbon $datetime
 *
 * @property-read \App\Models\Student|null $student
 *
 * @method static Builder|Post fromVerifiedStudents()
 * @method static Builder|Post wherePostStatus(string $status)
 * @method static Builder|Post fromStudentsInSection(string $section)
 * @method static Builder|Post wherePostMood(string $mood)
 * @method static Builder|Post stressedOrDrained()
 * @method static Builder|Post startingFrom(?\Illuminate\Support\Carbon $from)
 * @method static Builder|Post sortedByDateDirection(string $direction)
 *
 * @mixin HasAdminPagination
 * @mixin HasDateTimeDisplay
 */

class Post extends Model
{
    use HasAdminPagination, HasDateTimeDisplay, HasFilters;

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

    protected function displayDate(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->phFormat('M d, Y'),
        );
    }

    protected function displayTime(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->phFormat('h:i A'),
        );
    }

    public function scopeFromVerifiedStudents(Builder $query): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->whereStatusIsVerified());
    }

    public function scopeWherePostStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFromStudentsInSection(Builder $query, string $section): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->where('section', $section));
    }

    public function scopeWherePostMood(Builder $query, string $mood): Builder
    {
        return $query->where('mood', $mood);
    }

    public function scopeStressedOrDrained(Builder $query): Builder
    {
        return $query->whereIn('mood', [
            PostMood::Stressed->value,
            PostMood::Drained->value,
        ]);
    }

    public function scopeStartingFrom(Builder $query, ?Carbon $from): Builder
    {
        return $query->when($from, fn(Builder $q) => $q->where('datetime', '>=', $from));
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
                fn(Builder|Post $q, string $status) => $q->wherePostStatus($status)
            )
            ->when(
                $filters['section'] ?? null,
                fn(Builder|Post $q, string $section) => $q->fromStudentsInSection($section)
            )
            ->when(
                $filters['mood'] ?? null,
                fn(Builder|Post $q, string $mood) => $q->wherePostMood($mood)
            )
            ->sortedByDateDirection($filters['sort'] ?? 'latest');
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        return static::paginateForAdmin(static::queryVerifiedPostsWithFilters($filters));
    }

    public function isAtRisk(?Carbon $from = null): bool
    {
        return in_array($this->mood, [PostMood::Stressed, PostMood::Drained], true)
            && ($from === null || $this->datetime->greaterThanOrEqualTo($from));
    }

    public function daysAtRisk(): int
    {
        return (int) $this->datetime
            ->copy()
            ->startOfDay()
            ->diffInDays(Carbon::now()->startOfDay()) + 1;
    }

    /**
     * Mood counts keyed by mood value, for verified students within the period.
     *
     * @return \Illuminate\Support\Collection<string, int>
     */
    public static function getMoodCounts(string $period): \Illuminate\Support\Collection
    {
        $from = static::summaryReportPeriodStart($period);

        return static::query()
            ->fromVerifiedStudents()
            ->startingFrom($from)
            ->whereNotNull('mood')
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->pluck('total', 'mood');
    }

    /**
     * All posts for the given students, grouped by student id and date-desc.
     *
     * @param  array<int>  $studentIds
     * @return Collection<int, Collection<int, Post>>
     */
    public static function getPostsForStudents(array $studentIds): Collection
    {
        return static::query()
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('datetime')
            ->get(['student_id', 'mood', 'datetime'])
            ->groupBy('student_id');
    }
}