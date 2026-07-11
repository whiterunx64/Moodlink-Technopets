<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Support\PhTime;
use App\Traits\HasDateTimeDisplay;
use Carbon\Carbon;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Appointment Model
 *
 * @property int $id
 * @property int $student_id
 * @property string|null $context
 * @property string|null $note
 * @property \DateTimeInterface $datetime
 * @property AppointmentStatus $status
 *
 * @property-read \App\Models\Student|null $student
 * @property-read string $student_name
 * @property-read string $student_program
 * @property-read string $display_date
 * @property-read string $display_time
 * 
 * @method static Builder<Appointment> pending()
 * @method static Builder<Appointment> scheduled()
 * @method static Builder<Appointment> upcomingScheduled()
 * @method static Builder<Appointment> reminderDue()
 * @method static Builder<Appointment> awaitingCheckIn()
 * @method static Builder<Appointment> missedCheckIn()
 * @method static Builder<Appointment> missed()
 * @method static Builder<Appointment> history()
 * @method static Builder<Appointment> completedSessionEnded()
 * @method static Builder<Appointment> rejected()
 * @method static Builder<Appointment> scheduledOrCompleted()
 * @method static Builder<Appointment> startingFrom(?\DateTimeInterface $from)
 * @method static Builder<Appointment> forStudent(int $studentId)
 *
 * @mixin HasDateTimeDisplay
 */

class Appointment extends Model
{
    use HasDateTimeDisplay, HasFilters;

    public const CHECK_IN_GRACE_MINUTES = 30;

    public const SESSION_DURATION_MINUTES = 60;

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

    /**
     * The moment this appointment's check-in grace period closes.
     */
    public function checkInWindowEnd(): Carbon
    {
        return $this->datetime->copy()->addMinutes(self::CHECK_IN_GRACE_MINUTES);
    }

    /**
     * Whether this scheduled appointment is currently inside its check-in window —
     * it has started and the grace period has not yet elapsed.
     */
    public function isWithinCheckInWindow(): bool
    {
        if ($this->status !== AppointmentStatus::Scheduled) {
            return false;
        }

        $now = PhTime::nowUtc();

        return $now->greaterThanOrEqualTo($this->datetime)
            && $now->lessThanOrEqualTo($this->checkInWindowEnd());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Scheduled->value);
    }

    public function scopeUpcomingScheduled(Builder $query): Builder
    {
        return $query->scheduled()->where('datetime', '>=', PhTime::nowUtc());
    }

    public function scopeReminderDue(Builder $query): Builder
    {
        $now = PhTime::nowUtc();

        return $query->scheduled()
            ->where('datetime', '>=', $now->copy()->addMinutes(45))
            ->where('datetime', '<=', $now->copy()->addMinutes(60));
    }

    public function scopeAwaitingCheckIn(Builder $query): Builder
    {
        $now = PhTime::nowUtc();

        return $query->scheduled()
            ->where('datetime', '<=', $now)
            ->where('datetime', '>=', $now->copy()->subMinutes(self::CHECK_IN_GRACE_MINUTES));
    }

    public function scopeMissed(Builder $query): Builder
    {
        return $query
            ->where('status', AppointmentStatus::Missed->value)
            ->where('datetime', '<', PhTime::nowUtc());
    }

    public function scopeMissedCheckIn(Builder $query): Builder
    {
        return $query->scheduled()
            ->where('datetime', '<', PhTime::nowUtc()->subMinutes(self::CHECK_IN_GRACE_MINUTES));
    }

    public function scopeHistory(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Completed->value,
        ]);
    }

    public function scopeCompletedSessionEnded(Builder $query): Builder
    {
        $endedBy = PhTime::nowUtc()->subMinutes(self::SESSION_DURATION_MINUTES);

        return $query
            ->where('status', AppointmentStatus::Completed->value)
            ->whereBetween('datetime', [$endedBy->copy()->subDay(), $endedBy]);
    }

    public function scopeScheduledOrCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Scheduled->value,
            AppointmentStatus::Completed->value,
        ]);
    }

    public function scopeStartingFrom(Builder $query, ?\DateTimeInterface $from): Builder
    {
        return $query->when(
            $from,
            fn(Builder $q) => $q->where('datetime', '>=', $from)
        );
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

}