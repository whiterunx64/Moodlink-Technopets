<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\HasDateTimeDisplay;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Appointment Model
 *
 * @property int $id
 * @property int $student_id
 * @property string|null $context
 * @property string|null $note
 * @property AppointmentStatus $status
 * @property Carbon $datetime
 *
 * @property-read \App\Models\Student|null $student
 * @property-read string $student_name
 * @property-read string $student_program
 * @property-read string $display_date
 * @property-read string $display_time
 * 
 * @method static Builder|Appointment pending()
 * @method static Builder|Appointment scheduled()
 * @method static Builder|Appointment upcomingScheduled()
 * @method static Builder|Appointment missed()
 * @method static Builder|Appointment history()
 * @method static Builder|Appointment rejected()
 * @method static Builder|Appointment scheduledOrCompleted()
 * @method static Builder|Appointment startingFrom(?\Illuminate\Support\Carbon $from)
 * @method static Builder|Appointment forAppointmentTab(string $tab)
 * @method static Builder|Appointment forStudent(int $studentId)
 *
 * @mixin HasDateTimeDisplay
 */

class Appointment extends Model
{
    use HasDateTimeDisplay, HasFilters;

    protected $table = 'appointments';
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'context',
        'note',
        'status',
        'datetime',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Student, Appointment>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    protected function studentName(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->student?->name ?? 'Unknown',
        );
    }

    protected function studentProgram(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->student?->program ?? '',
        );
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Pending->value);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Scheduled->value);
    }

    /** Scheduled appointments that are still upcoming (drives the "scheduled" tab). */
    public function scopeUpcomingScheduled(Builder $query): Builder
    {
        return $query->scheduled()->where('datetime', '>=', Carbon::now());
    }

    public function scopeMissed(Builder $query): Builder
    {
        return $query
            ->where('status', AppointmentStatus::Missed->value)
            ->where('datetime', '<', Carbon::now());
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Completed->value,
        ]);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Rejected->value);
    }

    public function scopeScheduledOrCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Scheduled->value,
            AppointmentStatus::Completed->value,
        ]);
    }

    public function scopeOpenConsultation(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Pending->value,
            AppointmentStatus::Scheduled->value,
        ]);
    }

    public function scopeStartingFrom(Builder $query, ?Carbon $from): Builder
    {
        return $query->when($from, fn(Builder $q) => $q->where('datetime', '>=', $from));
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public static function getListForTab(string $tab): Collection
    {
        $status = AppointmentStatus::fromTab($tab);

        return static::query()
            ->with('student.appointments')
            ->where('status', $status->value)
            ->when($tab === 'scheduled', fn($q) => $q->where('datetime', '>=', now()))
            ->when($tab === 'missed', fn($q) => $q->where('datetime', '<', now()))
            ->orderBy('datetime')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public static function tabCounts(): object
    {
        return static::query()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS requests,
             SUM(CASE WHEN status = ? AND datetime >= NOW() THEN 1 ELSE 0 END) AS scheduled,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS history,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS rejected,
             SUM(CASE WHEN status = ? AND datetime < NOW() THEN 1 ELSE 0 END) AS missed',
                [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Scheduled->value,
                    AppointmentStatus::Completed->value,
                    AppointmentStatus::Rejected->value,
                    AppointmentStatus::Missed->value,
                ],
            )
            ->first();
    }
    public static function activeConsultationStudentIds(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }

        return static::query()
            ->scheduledOrCompleted()
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->unique()
            ->all();
    }

    public static function getScheduledCount(string $period): int
    {
        $from = static::summaryReportPeriodStart($period); // Get report start date from filter period.

        return static::query()
            ->scheduledOrCompleted()
            ->startingFrom($from)
            ->count();
    }
}