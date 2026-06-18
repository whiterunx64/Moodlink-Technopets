<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\HasDateTimeDisplay;
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
 * @method static Builder|Appointment forTab(string $tab)
 * @method static Builder|Appointment forStudent(int $studentId)
 *
 * @mixin HasDateTimeDisplay
 * @mixin HasStudentDisplay
 */

class Appointment extends Model
{
    use HasDateTimeDisplay, HasStudentDisplay;

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
        if ($tab === 'scheduled') {
            return $query->where('status', AppointmentStatus::Scheduled->value);
        } elseif ($tab === 'history') {
            return $query->whereIn('status', [
                AppointmentStatus::Completed->value,
                AppointmentStatus::Rejected->value,
            ]);
        } elseif ($tab === 'rejected') {
            return $query->where('status', AppointmentStatus::Rejected->value);
        } else {
            return $query->where('status', AppointmentStatus::Pending->value);
        }
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public static function getListForTab(string $tab): Collection
    {
        return static::query()
            ->with('student.appointments')
            ->forTab($tab)
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

    public static function getScheduledCount(string $period): int
    {
        $from = match ($period) {
            'this_week' => Carbon::now()->startOfWeek(),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        return static::query()
            ->whereIn('status', [
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Completed->value,
            ])
            ->when($from, fn(Builder $q) => $q->where('datetime', '>=', $from))
            ->count();
    }
}