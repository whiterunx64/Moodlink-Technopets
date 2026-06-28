<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PostMood;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
/**
 * @property int $id
 * @property int $account_id
 * @property PostMood|null $mood
 * @property string|null $summary
 * @property string|null $journal
 * @property \Illuminate\Support\Carbon $date
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

    public function scopeRecordedOnOrAfter(Builder $query, ?\DateTimeInterface $startDate): Builder
    {
        return $query->when(
            $startDate,
            fn(Builder $q) => $q->where('status_days.date', '>=', $startDate)
        );
    }

    public function scopeWhereStudentAtRisk(Builder $query): Builder
    {
        return $query->whereIn('mood', [PostMood::Stressed->value, PostMood::Drained->value]);
    }

    /**
     * Drop entries logged on or before the student's most recent counselor
     * session (latest scheduled/completed appointment), so a consultation wipes
     * the slate and the windows rebuild from after that session. With no such
     * session, every entry is kept.
     */
    public function scopeSinceRiskReset(Builder $query): Builder
    {
        return $query->whereRaw(
            "status_days.date > coalesce("
            . "(select max(a.datetime)::date from appointments a"
            . " where a.student_id = status_days.account_id and a.status in (?, ?)),"
            . " date '0001-01-01')",
            [AppointmentStatus::Scheduled->value, AppointmentStatus::Completed->value]
        );
    }

    /**
     * @param  array<int>  $studentIds
     * @return Collection<int, Collection<int, StatusDay>>
     */
    public static function moodCheckInsForStudents(array $studentIds): Collection
    {
        if (empty($studentIds)) {
            return new Collection();
        }

        return static::query()
            ->whereIn('account_id', $studentIds)
            ->sinceRiskReset()
            ->whereNotNull('mood')
            ->orderBy('date')
            ->get(['account_id', 'mood', 'date'])
            ->groupBy('account_id');
    }

    /**
     * @return Collection<int, StatusDay>
     */
    public static function recentMoodEntriesForStudent(int $studentId, int $limit = 5): Collection
    {
        return static::query()
            ->where('account_id', $studentId)
            ->whereNotNull('mood')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'mood', 'journal', 'date']);
    }

    /**
     * @return Collection<string, int>  mood value => entry count
     */
    public static function moodEntryCountsForStudent(int $studentId): Collection
    {
        return static::query()
            ->where('account_id', $studentId)
            ->whereNotNull('mood')
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->withCasts(['total' => 'integer'])
            ->pluck('total', 'mood');
    }

    /**
     * @return Collection<int, StatusDay>
     */
    public static function moodEntriesForStudentSince(int $studentId, \DateTimeInterface $start): Collection
    {
        return static::query()
            ->where('account_id', $studentId)
            ->whereNotNull('mood')
            ->where('date', '>=', $start)
            ->orderBy('date')
            ->get(['mood', 'date']);
    }

    public static function dailyLogStats(?\DateTimeInterface $start): object
    {
        return static::query()
            ->whereStudentIsVerified()
            ->recordedOnOrAfter($start)
            ->selectRaw('count(*) as total, count(distinct date) as active_days')
            ->first();
    }

    /**
     * @return Collection<string, int>
     */
    public static function moodCountsSince(?\DateTimeInterface $start, ?string $program = null): Collection
    {
        return static::query()
            ->whereStudentIsVerified()
            ->when($program, fn(Builder $query) => $query->whereHas(
                'student',
                fn(Builder $student) => $student->where('program', $program),
            ))
            ->recordedOnOrAfter($start)
            ->whereNotNull('mood')
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->withCasts(['total' => 'integer'])
            ->pluck('total', 'mood');
    }

    /**
     * @return Collection<int, StatusDay>  one row per program, ordered by program name
     */
    public static function StudentMoodSummaryByProgram(?\DateTimeInterface $start): Collection
    {
        return static::query()
            ->join('students', 'students.id', '=', 'status_days.account_id')
            ->where('students.status', StudentStatus::Verified->value)
            ->whereNotNull('students.program')
            ->whereNotNull('status_days.mood')
            ->recordedOnOrAfter($start)
            ->selectRaw(
                '
                students.program,
                COUNT(*) AS total_mood_entries,
                SUM(CASE WHEN status_days.mood = ? THEN 1 ELSE 0 END)
                    as excited_count,
                SUM(CASE WHEN status_days.mood = ? THEN 1 ELSE 0 END)
                    as content_count,
                SUM(CASE WHEN status_days.mood = ? THEN 1 ELSE 0 END)
                    as stressed_count,
                SUM(case when status_days.mood = ? THEN 1 ELSE 0 END)
                    as drained_count
                ',
                [
                    PostMood::Excited->value,
                    PostMood::Content->value,
                    PostMood::Stressed->value,
                    PostMood::Drained->value
                ],
            )
            ->groupBy('students.program')
            ->orderBy('students.program')
            ->withCasts([
                'total_mood_entries' => 'integer',
                'excited_count' => 'integer',
                'content_count' => 'integer',
                'stressed_count' => 'integer',
                'drained_count' => 'integer',
            ])
            ->get();
    }
}