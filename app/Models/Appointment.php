<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\YearLevel;
use App\Traits\HasInitials;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Appointment Model
 *
 * @property int                        $id
 * @property int                        $student_id
 * @property string|null                $context
 * @property string|null                $note
 * @property AppointmentStatus          $status
 * @property \Illuminate\Support\Carbon $datetime
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

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public static function getListForTab(string $tab): Collection
    {
        return static::query()
            ->with('student')
            ->forTab($tab)
            ->orderBy('datetime')
            ->get()
            ->map(fn(Appointment $appointment): array => [
                'id' => $appointment->id,
                'student_id' => $appointment->student_id,
                'context' => $appointment->context,
                'note' => $appointment->note,
                'status' => $appointment->status->label(),
                'date' => $appointment->datetime->toFormattedDayDateString(),
                'time' => $appointment->datetime->format('h:i A'),
                'studentName' => $appointment->student?->name ?? 'Unknown',
                'section' => $appointment->student?->section ?? '',
                'studentProfile' => $appointment->studentProfile($appointment->student),
            ]);
    }

    public static function getCountsPerStatusTab(): array
    {
        $counts = static::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'requests' => $counts->get(AppointmentStatus::Pending->value, 0),
            'scheduled' => $counts->get(AppointmentStatus::Scheduled->value, 0),
            'history' => $counts->get(AppointmentStatus::Completed->value, 0)
                + $counts->get(AppointmentStatus::Rejected->value, 0),
            'rejected' => $counts->get(AppointmentStatus::Rejected->value, 0),
        ];
    }

    private function studentProfile(?Student $student): array
    {
        if ($student === null) {
            return [
                'initials' => '',
                'section' => '',
                'course' => '',
                'yearLevel' => '',
                'studentId' => '',
                'totalAppointments' => 0,
                'history' => [],
            ];
        }

        return [
            'initials' => $this->getInitialsFromName($student->name),
            'section' => $student->section,
            'course' => $student->section,
            'yearLevel' => YearLevel::tryFrom($student->year_level)?->label() ?? '',
            'studentId' => $student->student_number,
            'totalAppointments' => static::forStudent($student->id)->count(),
            'history' => $this->getStudentAppointmentHistory(),
        ];
    }

    private function getStudentAppointmentHistory(): array
    {
        return static::forStudent($this->student_id)
            ->orderByDesc('datetime')
            ->get()
            ->map(fn(Appointment $appointment): array => [
                'context' => $appointment->context,
                'date' => $appointment->datetime->toFormattedDayDateString(),
                'time' => $appointment->datetime->format('h:i A'),
                'note' => $appointment->note,
                'status' => $appointment->status->label(),
            ])
            ->toArray();
    }
}