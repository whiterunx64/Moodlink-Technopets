<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PostMood;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Services\SummaryReportService;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        return $query->whereIn('mood', [PostMood::Stressed->value, PostMood::Drained->value]);
    }

    /**
     * Keep only entries logged on or after the student became at risk
     * (students.risk_start_date), so summaries reflect the current episode.
     * When risk_start_date is null, every entry is kept.
     */
    public function scopeWithinRiskWindow(Builder $query): Builder
    {
        return $query->whereRaw(
            'status_days.date >= coalesce('
            . '(select s.risk_start_date from students s where s.id = status_days.account_id),'
            . ' status_days.date)'
        );
    }

    /**
     * Window 1 (entry): verified students whose warning moods (Stressed/Drained)
     * within the recent window reach the risk threshold and who are not already
     * flagged or in an open consultation. These should be flagged At Risk.
     *
     * @return list<int>  student ids
     */
    public static function studentsEnteringRiskWindow(\Carbon\Carbon $since): array
    {
        return static::query()
            ->join('students', 'students.id', '=', 'status_days.account_id')
            ->where('students.status', StudentStatus::Verified->value)
            ->whereNull('students.risk_start_date')
            ->whereNotExists(fn($sub) => $sub
                ->select(DB::raw(1))
                ->from('appointments')
                ->whereColumn('appointments.student_id', 'students.id')
                ->whereIn('appointments.status', [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Scheduled->value,
                ]))
            ->whereIn('status_days.mood', SummaryReportService::warningMoodValues())
            ->where('status_days.date', '>=', $since)
            ->groupBy('status_days.account_id')
            ->havingRaw('count(*) >= ?', [SummaryReportService::RISK_THRESHOLD])
            ->pluck('status_days.account_id')
            ->map(fn($id): int => (int) $id)
            ->all();
    }

    /**
     * Window 2 (recovery override): flagged students whose recovery moods
     * (Content/Excited) logged since they became at risk reach the recovery
     * threshold. These should be cleared from the At Risk list.
     *
     * @return list<int>  student ids
     */
    public static function studentsRecoveredInWindow(): array
    {
        return static::query()
            ->join('students', 'students.id', '=', 'status_days.account_id')
            ->where('students.status', StudentStatus::Verified->value)
            ->whereNotNull('students.risk_start_date')
            ->whereIn('status_days.mood', SummaryReportService::recoveryMoodValues())
            ->whereColumn('status_days.date', '>=', 'students.risk_start_date')
            ->groupBy('status_days.account_id')
            ->havingRaw('count(*) >= ?', [SummaryReportService::RECOVERY_THRESHOLD])
            ->pluck('status_days.account_id')
            ->map(fn($id): int => (int) $id)
            ->all();
    }

    /**
     * Day-by-day entries for the given students within the period, grouped by
     * student id and ordered newest first. Used to build the at-risk summaries
     * (dominant moods, days at risk, last log) from the same source as scoring.
     *
     * @param  array<int>  $studentIds
     * @return Collection<int, Collection<int, StatusDay>>
     */
    public static function entriesForStudents(array $studentIds, ?Carbon $from): Collection
    {
        if (empty($studentIds)) {
            return new Collection();
        }

        return static::query()
            ->whereIn('account_id', $studentIds)
            ->recordedOnOrAfter($from)
            ->withinRiskWindow()
            ->whereNotNull('mood')
            ->orderByDesc('date')
            ->get(['account_id', 'mood', 'date'])
            ->groupBy('account_id');
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