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
        return $query->whereHas('student', fn(Builder $q) => $q->verified());
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
        return static::paginateForAdmin(static::queryVerifiedPostsWithFilters($filters))
            ->through(fn(Post $post): array => [
                'id' => $post->id,
                'content' => $post->content,
                'mood' => $post->mood?->value,
                'status' => $post->status?->value,
                'date' => $post->display_date,
                'time' => $post->display_time,
                'section' => $post->student?->section ?? '',
                'anonymous_name' => $post->student?->anonymous_name,
                'last_name' => $post->student?->last_name,
                'first_name' => $post->student?->first_name,
            ]);
    }
    public static function getMoodDistribution(string $period): array
    {
        $from = static::summaryReportPeriodStart($period);

        $rows = static::query()
            ->fromVerifiedStudents()
            ->startingFrom($from)
            ->whereNotNull('mood')
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->pluck('total', 'mood');

        $grand = $rows->sum();

        return collect(PostMood::cases())
            ->map(function (PostMood $mood) use ($rows, $grand): array {
                $count = (int) $rows->get($mood->value, 0);

                return [
                    'label' => $mood->value,
                    'count' => $count,
                    'pct' => $grand > 0 ? (int) round($count / $grand * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    public static function getAtRiskSummary(array $studentIds, string $period): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $from = static::summaryReportPeriodStart($period);

        return static::query()
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('datetime')
            ->get(['student_id', 'mood', 'datetime'])
            ->groupBy('student_id')
            ->map(function (Collection $posts) use ($from): array {
                $atRiskPosts = $posts->filter(
                    fn(Post $post): bool => static::isAtRiskPost($post, $from)
                );

                // Posts are date-desc, so the last at-risk post is the earliest at-risk log.
                $firstAtRiskPost = $atRiskPosts->last();

                return [
                    'moods' => $atRiskPosts
                        ->groupBy(fn(Post $post): string => $post->mood->value)
                        ->map(fn(Collection $group): int => $group->count())
                        ->sortDesc()
                        ->keys()
                        ->all(),

                    'daysAtRisk' => static::calculateDaysAtRisk($firstAtRiskPost),

                    'lastLog' => $posts->first()?->datetime?->diffForHumans(),
                ];
            })
            ->all();
    }

    public static function getAvgDailyLogs(string $period, int $totalMoodLogs): int
    {
        $days = static::summaryReportPeriodDays($period);

        return (int) round($totalMoodLogs / $days);
    }

    // Private Helpers
    private static function isAtRiskMood(?PostMood $mood): bool
    {
        return in_array($mood, [
            PostMood::Stressed,
            PostMood::Drained,
        ], true);
    }

    private static function isAtRiskPost(Post $post, ?Carbon $from): bool
    {
        return static::isAtRiskMood($post->mood)
            && ($from === null || $post->datetime->greaterThanOrEqualTo($from));
    }

    private static function calculateDaysAtRisk(?Post $post): int
    {
        if ($post === null) {
            return 0;
        }

        return (int) $post->datetime
            ->copy()
            ->startOfDay()
            ->diffInDays(Carbon::now()->startOfDay()) + 1;
    }
}