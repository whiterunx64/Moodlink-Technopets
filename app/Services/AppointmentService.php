<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use DomainException;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * @property int|null $takenBy
 * @property Carbon $datetime
 * @property int $id
 */

class AppointmentService
{

  /**
   * @throws DomainException when the appointment is not pending, or its slot is
   *                         missing or already booked by someone else.
   */
  public function approve(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeApproved($appointment);

    try {
      DB::transaction(function () use ($appointment) {
        $slot = $this->findSlotForAppointment($appointment);
        $this->ensureSlotCanBeBooked($slot, $appointment);
        $this->ensureStudentHasNoOtherBookedSlot($appointment, $slot);

        $slot->update(['takenBy' => $appointment->student_id]);
        $appointment->update(['status' => AppointmentStatus::Scheduled->value]);
      });
    } catch (UniqueConstraintViolationException) {
      // Two approvals for the same student slipped past the guard at once;
      throw new DomainException('This student already has a booked slot. Complete or release it before booking another.');
    }
  }

  /**
   * @throws DomainException when the appointment is not in Pending status.
   */
  public function deny(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeDenied($appointment);

    $appointment->update(['status' => AppointmentStatus::Rejected->value]);
  }

  /**
   * @throws DomainException when the appointment is not in Scheduled status.
   */
  public function complete(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeCompleted($appointment);

    $appointment->update(['status' => AppointmentStatus::Completed->value]);

    AvailableSchedule::where('takenBy', $appointment->student_id)->delete();
  }

  /**
   * @throws DomainException when a slot already exists at the same datetime.
   */
  public function addSlot(string $datetime): void
  {
    $slotAt = Carbon::createFromFormat('Y-m-d H:i:s', $datetime, 'Asia/Manila')->utc();

    $this->ensureNoSlotExistsAtSameTime($slotAt);

    AvailableSchedule::create(['datetime' => $slotAt]);
  }

  /**
   * @throws DomainException when the slot is already booked.
   */
  public function deleteSlot(AvailableSchedule $slot): void
  {
    $this->ensureSlotIsNotAlreadyBooked($slot);

    $slot->delete();
  }

  // ── Private Guards ────────────────────────────────────────────────────────

  private function ensureAppointmentCanBeApproved(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw new DomainException('Only pending appointments can be approved.');
    }
  }

  private function ensureAppointmentCanBeDenied(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw new DomainException('Only pending appointments can be denied.');
    }
  }

  private function ensureAppointmentCanBeCompleted(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Scheduled) {
      throw new DomainException('Only scheduled appointments can be marked as completed.');
    }
  }

  private function ensureNoSlotExistsAtSameTime(Carbon $datetime): void
  {
    $alreadyExists = AvailableSchedule::where('datetime', $datetime)->exists();

    if ($alreadyExists) {
      throw new DomainException('A slot already exists at this time.');
    }
  }

  private function ensureSlotIsNotAlreadyBooked(AvailableSchedule $slot): void
  {
    if ($slot->takenBy !== null) {
      throw new DomainException('Cannot delete a slot that is already booked.');
    }
  }

  private function findSlotForAppointment(Appointment $appointment): ?AvailableSchedule
  {
    return AvailableSchedule::whereBetween('datetime', [
      $appointment->datetime->copy()->startOfMinute(),
      $appointment->datetime->copy()->endOfMinute(),
    ])->lockForUpdate()->first();
  }

  private function ensureSlotCanBeBooked(?AvailableSchedule $slot, Appointment $appointment): void
  {
    if ($slot === null) {
      throw new DomainException('No available slot matches this appointment time.');
    }

    if ($slot->takenBy !== null && $slot->takenBy !== $appointment->student_id) {
      throw new DomainException('That time slot has already been booked by another student.');
    }
  }

  private function ensureStudentHasNoOtherBookedSlot(Appointment $appointment, AvailableSchedule $slot): void
  {
    $holdsAnotherSlot = AvailableSchedule::where('takenBy', $appointment->student_id)
      ->where('id', '!=', $slot->id)
      ->exists();

    if ($holdsAnotherSlot) {
      throw new DomainException('This student already has a booked slot. Complete or release it before booking another.');
    }
  }
}
