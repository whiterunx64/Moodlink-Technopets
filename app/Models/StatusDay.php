<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostMood;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
/**
 * @property int $id
 * @property int $account_id
 * @property PostMood|null $mood
 * @property string|null $summary
 * @property string|null $journal
 * @property Carbon $date
 *
 * @property-read Student|null $student
 */
class StatusDay extends Model
{
    use HasFilters;

    protected $table = 'status_days';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'account_id',
        'mood',
        'summary',
        'journal',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'mood' => PostMood::class,
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Student, StatusDay>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'account_id', 'id');
    }

    public function scopeWhereStudentIsVerified(Builder $query): Builder
    {
        return $query->whereHas('student', function (Builder $studentQuery) {
            $studentQuery->where('status', StudentStatus::Verified->value);
        });
    }

    public function scopeWhereStudentAtRisk(Builder $query): Builder
    {
        return $query->whereIn('mood', [PostMood::Stressed->value, PostMood::Drained->value, PostMood::Content->value]);
    }

    public static function avgDailyLogs(string $period): int
    {
        $stats = static::query()
            ->whereStudentIsVerified()
            ->recordedOnOrAfter(static::summaryReportPeriodStart($period))
            ->selectRaw('count(*) as total, count(distinct date) as active_days')
            ->first();

        $activeDays = (int) $stats->active_days;

        return $activeDays > 0 ? (int) round((int) $stats->total / $activeDays) : 0;
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public static function getMoodCounts(string $period): \Illuminate\Support\Collection
    {
        return static::query()
            ->whereStudentIsVerified()
            ->recordedOnOrAfter(static::summaryReportPeriodStart($period))
            ->whereNotNull('mood')
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->pluck('total', 'mood');
    }
}