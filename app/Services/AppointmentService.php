<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use DomainException;

class AppointmentService
{

  /**
   * @throws DomainException when the appointment is not in Pending status.
   */
  public function approve(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeApproved($appointment);

    AvailableSchedule::where('datetime', $appointment->datetime)
      ->first()
        ?->update(['isTaken' => true]);

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
      'isTaken' => false,
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
    if ($slot->isTaken) {
      throw new DomainException('Cannot delete a slot that is already booked.');
    }
  }
}
