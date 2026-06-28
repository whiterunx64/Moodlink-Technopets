<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Support\PhTime;
class SlotManager
{
  public function __construct(
    private readonly Validator $validator,
  ) {
  }

  /**
   * @throws \App\Exceptions\AppointmentException when a slot already exists at the same datetime,
   * the datetime is in the past, or falls outside GCU operating hours.
   */
  public function addSlot(string $datetime): void
  {
    $slotAt = PhTime::toUtc($datetime);
    $phTime = PhTime::fromUtc($slotAt);

    $this->validator->ensureSlotIsNotInThePast($phTime);
    $this->validator->ensureSlotIsWithinWorkingHours($phTime);
    $this->validator->ensureNoSlotExistsAtSameTime($slotAt);

    AvailableSchedule::create(['datetime' => $slotAt]);
  }

  /**
   * @throws \App\Exceptions\AppointmentException when the slot is already booked.
   */
  public function deleteSlot(AvailableSchedule $slot): void
  {
    $this->validator->ensureSlotIsNotAlreadyBooked($slot);

    $slot->delete();
  }

  public function findSlotForAppointment(Appointment $appointment): ?AvailableSchedule
  {
    return AvailableSchedule::whereBetween('datetime', [
      $appointment->datetime->copy()->startOfMinute(),
      $appointment->datetime->copy()->endOfMinute(),
    ])->lockForUpdate()->first();
  }
}