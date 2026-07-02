<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Support\PhTime;
use App\Traits\HasDateTimeDisplay;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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
 * @method static Builder|Post fromProgram(string $program)
 * @method static Builder|Post whereStatusIs(PostStatus $status)
 * @method static Builder|Post whereStatusFilter(?string $status)
 * @method static Builder|Post whereProgramFilter(?string $program)
 * @method static Builder|Post whereMoodFilter(?string $mood)
 * @method static Builder|Post startingFrom(?\Illuminate\Support\Carbon $from)
 * @method static Builder|Post sortedByDateDirection(string $direction)
 *
 * @mixin HasDateTimeDisplay
 */

class Post extends Model
{
    use HasDateTimeDisplay, HasFilters;

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

    /**
     * @return HasMany<PostReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(PostReport::class, 'post_id');
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

    public function scopeFromProgram(Builder $query, string $program): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->where('program', $program));
    }

    public function scopeWhereStatusIs(Builder $query, PostStatus $status): Builder
    {
        return $query->where('status', $status->value);
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

    public function scopeWhereStatusFilter(Builder $query, ?string $status): Builder
    {
        return $query->when(
            $status,
            fn(Builder $query, string $status) => $query->where('status', $status)
        );
    }

    public function scopeWhereProgramFilter(Builder $query, ?string $program): Builder
    {
        return $query->when(
            $program,
            fn(Builder $query, string $program) => $query->fromProgram($program)
        );
    }

    public function scopeWhereMoodFilter(Builder $query, ?string $mood): Builder
    {
        return $query->when(
            $mood,
            fn(Builder $query, string $mood) => $query->where('mood', $mood)
        );
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        return static::query()
            ->with('student')
            ->fromVerifiedStudents()
            ->whereStatusFilter($filters['status'])
            ->whereProgramFilter($filters['program'])
            ->whereMoodFilter($filters['mood'])
            ->sortedByDateDirection($filters['sort'])
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Total, safe, flagged and archived post counts for the management index header.
     *
     * @return object{total: int, safe: int, flagged: int, archived: int}
     */
    public static function statusCount(): object
    {
        return static::query()
            ->selectRaw(
                '
                COUNT(*) AS total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)
                    AS safe,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)
                    AS flagged,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)
                    AS archived
                ',
                [
                    PostStatus::Safe->value,
                    PostStatus::Flagged->value,
                    PostStatus::Archived->value,
                ],
            )
            ->withCasts([
                'total' => 'integer',
                'safe' => 'integer',
                'flagged' => 'integer',
                'archived' => 'integer',
            ])
            ->first();
    }

    /**
     * @return object{total: int, flagged: int}
     */
    public static function totalAndFlaggedCountsForStudent(int $studentId): object
    {
        return static::query()
            ->where('student_id', $studentId)
            ->selectRaw(
                'count(*) as total, sum(case when status = ? then 1 else 0 end) as flagged',
                [PostStatus::Flagged->value],
            )
            ->withCasts(['total' => 'integer', 'flagged' => 'integer'])
            ->first();
    }

    /**
     * Today's safe/flagged posts from verified students, newest first, with the
     * student loaded for display.
     *
     * @return Collection<int, Post>
     */
    public static function dashboardRecentEntries(string $period = 'today'): Collection
{
    $start = match ($period) {
        'week' => PhTime::now()->startOfWeek()->utc(),
        'month' => PhTime::now()->startOfMonth()->utc(),
        default => PhTime::todayStartUtc(),
    };

    return static::query()
        ->with('student')
        ->whereIn('status', [
            PostStatus::Safe->value,
            PostStatus::Flagged->value,
        ])
        ->where('datetime', '>=', $start)
        ->orderByDesc('datetime')
        ->orderByDesc('id')
        ->get();
}
}