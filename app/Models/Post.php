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
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property string|null $content
 * @property PostMood|null $mood
 * @property PostStatus $status
 * @property  Carbon $datetime
 *
 * @property-read \App\Models\Student|null $student
 *
 * @method static Builder|Post fromVerifiedStudents()
 * @method static Builder|Post withPostStatus(string $status)
 * @method static Builder|Post fromStudentSection(string $section)
 * @method static Builder|Post withPostMood(string $mood)
 * @method static Builder|Post sortedByDateDirection(string $direction)
 * @method static Builder|Post fromVerifiedStudents()
 * @method static Builder|Post withPostMood(string $mood)
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
    public static function getMoodDistribution(string $period): array
    {
        $from = static::periodFrom($period);

        $rows = static::query()
            ->fromVerifiedStudents()
            ->when($from, fn(Builder $q) => $q->where('datetime', '>=', $from))
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

    public static function getAvgDailyLogs(string $period, int $totalMoodLogs): int
    {
        $days = match ($period) {
            'this_week' => 7,
            'this_month' => (int) Carbon::now()->daysInMonth,
            default => 1,
        };

        return (int) round($totalMoodLogs / $days);
    }

    private static function periodFrom(string $period): ?Carbon
    {
        return match ($period) {
            'this_week' => Carbon::now()->startOfWeek(),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };
    }
}