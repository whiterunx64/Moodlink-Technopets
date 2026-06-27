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
            ->pluck('total', 'mood');
    }
}