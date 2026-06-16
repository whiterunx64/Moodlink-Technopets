<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use DomainException;

class AppointmentService
{

  /**
   * @throws DomainException when the appointment is not pending, or its slot is
   *                         missing or already booked by someone else.
   */
  public function approve(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeApproved($appointment);

    $slot = $this->findSlotForAppointment($appointment);
    $this->ensureSlotCanBeBooked($slot, $appointment);

    $slot->update(['takenBy' => $appointment->student_id]);

    $appointment->update(['status' => AppointmentStatus::Scheduled->value]);
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
  }

  /**
   * @throws DomainException when a slot already exists at the same datetime.
   */
  public function addSlot(string $datetime): void
  {
    $this->ensureNoSlotExistsAtSameTime($datetime);

    AvailableSchedule::create([
      'datetime' => $datetime,
    ]);
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

  private function ensureNoSlotExistsAtSameTime(string $datetime): void
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
    ])->first();
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
}
