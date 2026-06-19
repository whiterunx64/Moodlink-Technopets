<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\HasDateTimeDisplay;
use App\Traits\HasFilters;
use App\Traits\HasStudentDisplay;
use Illuminate\Database\Eloquent\Builder;
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
 *
 * @method static Builder|Appointment pending()
 * @method static Builder|Appointment scheduled()
 * @method static Builder|Appointment history()
 * @method static Builder|Appointment rejected()
 * @method static Builder|Appointment scheduledOrCompleted()
 * @method static Builder|Appointment startingFrom(?\Illuminate\Support\Carbon $from)
 * @method static Builder|Appointment forAppointmentTab(string $tab)
 * @method static Builder|Appointment forStudent(int $studentId)
 *
 * @mixin HasDateTimeDisplay
 * @mixin HasStudentDisplay
 */

class Appointment extends Model
{
    use HasDateTimeDisplay, HasStudentDisplay, HasFilters;

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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Pending->value);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Scheduled->value);
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Completed->value,
            AppointmentStatus::Rejected->value,
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

    public function scopeStartingFrom(Builder $query, ?Carbon $from): Builder
    {
        return $query->when($from, fn(Builder $q) => $q->where('datetime', '>=', $from));
    }

    /**
     * @param Builder|Appointment $query
     */
    public function scopeForAppointmentTab(Builder $query, string $tab): Builder
    {
        if ($tab === 'scheduled') {
            return $query->scheduled();
        }

        if ($tab === 'history') {
            return $query->history();
        }

        if ($tab === 'rejected') {
            return $query->rejected();
        }

        return $query->pending();
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public static function getListForTab(string $tab): Collection
    {
        return static::query()
            ->with('student.appointments')
            ->forAppointmentTab($tab)
            ->orderBy('datetime')
            ->get()
            ->map(fn(Appointment $appointment): array => [
                'id' => $appointment->id,
                'student_id' => $appointment->student_id,
                'context' => $appointment->context,
                'note' => $appointment->note,
                'status' => $appointment->status->value,
                'date' => $appointment->displayDate(),
                'time' => $appointment->displayTime(),
                'student_name' => $appointment->displayStudentName(),
                'section' => $appointment->displayStudentSection(),
                'student_profile' => $appointment->studentProfile(),
            ]);
    }

    public static function getCountsPerStatusTab(): array
    {
        // Get appointment totals grouped by status.
        $appointmentCounts = static::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'requests' => $appointmentCounts->get(AppointmentStatus::Pending->value, 0),
            'scheduled' => $appointmentCounts->get(AppointmentStatus::Scheduled->value, 0),

            // History includes completed and rejected appointments.
            'history' => $appointmentCounts->get(AppointmentStatus::Completed->value, 0)
                + $appointmentCounts->get(AppointmentStatus::Rejected->value, 0),

            'rejected' => $appointmentCounts->get(AppointmentStatus::Rejected->value, 0),
        ];
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