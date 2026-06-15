<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;

class AppointmentService
{
  public function approve(Appointment $appointment): void
  {
    $appointment->update(['status' => AppointmentStatus::Scheduled->value]);
  }

  public function deny(Appointment $appointment): void
  {
    $appointment->update(['status' => AppointmentStatus::Rejected->value]);
  }

  public function complete(Appointment $appointment): void
  {
    $appointment->update(['status' => AppointmentStatus::Completed->value]);
  }

  public function addSlot(string $datetime): void
  {
    AvailableSchedule::create([
      'datetime' => $datetime,
      'isTaken'  => false,
    ]);
  }

  public function deleteSlot(AvailableSchedule $slot): void
  {
    $slot->delete();
  }
}
