<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Student;
use App\Support\PhTime;
use Carbon\Carbon;
class Validator
{
  /**
   * @throws AppointmentException when the student already has a pending or scheduled consultation.
   */
  public function ensureStudentHasNoOpenConsultation(Student $student): void
  {
    $hasOpenConsultation = Appointment::where('student_id', $student->id)
      ->whereIn('status', [
        AppointmentStatus::Pending->value,
        AppointmentStatus::Scheduled->value,
      ])
      ->exists();

    if ($hasOpenConsultation) {
      throw AppointmentException::studentAlreadyHasOpenConsultation();
    }
  }

  /**
   * @throws AppointmentException when not pending, in the past, or outside working hours.
   */
  public function ensureAppointmentCanBeApproved(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw AppointmentException::appointmentMustBePendingToApprove();
    }

    $phTime = PhTime::fromUtc($appointment->datetime);

    $this->ensureSlotIsNotInThePast($phTime);
    $this->ensureSlotIsWithinWorkingHours($phTime);
  }

  /**
   * @throws AppointmentException when the appointment is not in Pending status.
   */
  public function ensureAppointmentCanBeRejected(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw AppointmentException::appointmentMustBePendingToReject();
    }
  }

  /**
   * @throws AppointmentException when the appointment is not in Scheduled status.
   */
  public function ensureAppointmentCanBeCompleted(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Scheduled) {
      throw AppointmentException::appointmentMustBeScheduledToComplete();
    }
  }

  /**
   * @throws AppointmentException when "now" is before the start or past the grace period.
   */
  public function ensureWithinCheckInWindow(Appointment $appointment): void
  {
    $now = PhTime::nowUtc();

    if (
      $now->lessThan($appointment->datetime) ||
      $now->greaterThan($appointment->checkInWindowEnd())
    ) {
      throw AppointmentException::appointmentCheckInOutsideWindow();
    }
  }

  /**
   * @throws AppointmentException when a slot already exists at the same datetime.
   */
  public function ensureNoSlotExistsAtSameTime(Carbon $datetime): void
  {
    $alreadyExists = AvailableSchedule::where('datetime', $datetime)->exists();

    if ($alreadyExists) {
      throw AppointmentException::slotAlreadyExistsAtTime();
    }
  }

  /**
   * @throws AppointmentException when the slot is already booked.
   */
  public function ensureSlotIsNotAlreadyBooked(AvailableSchedule $slot): void
  {
    if ($slot->takenBy !== null) {
      throw AppointmentException::bookedSlotCannotBeDeleted();
    }
  }

  /**
   * @throws AppointmentException when the slot is missing or booked by another student.
   */
  public function ensureSlotCanBeBooked(?AvailableSchedule $slot, Appointment $appointment): void
  {
    if ($slot === null) {
      throw AppointmentException::noAvailableSlotForAppointmentTime();
    }

    if ($slot->takenBy !== null && $slot->takenBy !== $appointment->student_id) {
      throw AppointmentException::slotAlreadyBookedByAnotherStudent();
    }
  }

  /**
   * @throws AppointmentException when the student already holds a different booked slot.
   */
  public function ensureStudentHasNoOtherBookedSlot(Appointment $appointment, AvailableSchedule $slot): void
  {
    $holdsAnotherSlot = AvailableSchedule::where('takenBy', $appointment->student_id)
      ->where('id', '!=', $slot->id)
      ->exists();

    if ($holdsAnotherSlot) {
      throw AppointmentException::studentAlreadyHasBookedSlot();
    }
  }

  /**
   * @throws AppointmentException when the slot datetime is in the past.
   */
  public function ensureSlotIsNotInThePast(Carbon $phTime): void
  {
    if ($phTime->isPast()) {
      throw AppointmentException::slotDateIsInThePast();
    }
  }

  /**
   * @throws AppointmentException when the slot falls outside GCU operating hours.
   */
  public function ensureSlotIsWithinWorkingHours(Carbon $phTime): void
  {
    $minutes = $phTime->hour * 60 + $phTime->minute;

    // 8:00 AM = 480 min, 6:00 PM = 1080 min
    if ($minutes < 480 || $minutes > 1080) {
      throw AppointmentException::slotTimeOutsideWorkingHours();
    }
  }
}