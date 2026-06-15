<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\YearLevel;
use App\Traits\HasInitials;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                              $id
 * @property int                              $student_id
 * @property string|null                      $context
 * @property string|null                      $note
 * @property AppointmentStatus                $status
 * @property \Illuminate\Support\Carbon       $datetime
 *
 * @property-read Student|null $student
 *
 * @method static Builder|Appointment pending()
 * @method static Builder|Appointment scheduled()
 * @method static Builder|Appointment history()
 * @method static Builder|Appointment rejected()
 * @method static Builder|Appointment forTab(string $tab)
 * @method static Builder|Appointment forStudent(int $studentId)
 */
class Appointment extends Model
{
    use HasInitials;
    protected $table = 'appointments';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'context',
        'note',
        'status',
        'datetime',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'status' => AppointmentStatus::class,
    ];


    /**
     * The student who booked this appointment.
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

    /**
     * filter rows to match the given tab name.
     */
    public function scopeForTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'scheduled' => $query->where('status', AppointmentStatus::Scheduled->value),
            'history' => $query->whereIn('status', [
                AppointmentStatus::Completed->value,
                AppointmentStatus::Rejected->value,
            ]),
            'rejected' => $query->where('status', AppointmentStatus::Rejected->value),
            default => $query->where('status', AppointmentStatus::Pending->value),
        };
    }

    /**
     * appointments belonging to a specific student.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public static function getListForTab(string $tab): \Illuminate\Support\Collection
    {
        return static::query()
            ->with('student')
            ->forTab($tab)
            ->orderBy('datetime')
            ->get()
            ->map(fn(Appointment $appointment) => $appointment->toListRow());
    }

    /**
     * Return appointment counts grouped by UI tab name.
     */
    public static function getCountsPerStatusTab(): array
    {
        $counts = static::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'requests' => $counts[AppointmentStatus::Pending->value] ?? 0,
            'scheduled' => $counts[AppointmentStatus::Scheduled->value] ?? 0,
            'history' => ($counts[AppointmentStatus::Completed->value] ?? 0)
                + ($counts[AppointmentStatus::Rejected->value] ?? 0),
            'rejected' => $counts[AppointmentStatus::Rejected->value] ?? 0,
        ];
    }

    /**
     * Shape this appointment into a flat array for the list view.
     */
    public function toListRow(): array
    {
        $student = $this->student;

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'context' => $this->context,
            'note' => $this->note,
            'status' => $this->status->label(),
            'date' => $this->datetime->format('M d, Y'),
            'time' => $this->datetime->format('h:i A'),
            'studentName' => $student?->name ?? 'Null',
            'section' => $student?->section ?? '',
            'studentProfile' => $this->buildStudentProfile($student),
        ];
    }

    /**
     * Student profile data structure for appointment modal.
     */
    private function buildStudentProfile(?Student $student): array
    {
        return [
            'initials' => $this->getInitialsFromName($student?->name ?? ''),
            'section' => $student?->section ?? '',
            'course' => $student?->section ?? '',
            'yearLevel' => YearLevel::tryFrom($student?->year_level ?? 0)?->label() ?? '',
            'studentId' => $student?->student_number ?? '',
            'totalAppointments' => static::forStudent($this->student_id)->count(),
            'history' => $this->getStudentAppointmentHistory(),
        ];
    }

    /**
     * Student appointment history data structure (newest first).
     */
    private function getStudentAppointmentHistory(): array
    {
        return static::forStudent($this->student_id)
            ->orderByDesc('datetime')
            ->get()
            ->map(fn(Appointment $appointment) => [
                'context' => $appointment->context,
                'date' => $appointment->datetime->format('M d, Y'),
                'time' => $appointment->datetime->format('h:i A'),
                'note' => $appointment->note,
                'status' => $appointment->status->label(),
            ])
            ->toArray();
    }

}
