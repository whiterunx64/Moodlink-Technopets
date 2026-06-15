<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use Illuminate\Support\Collection;

class AppointmentService
{
  public function getByTab(string $tab): Collection
  {
    $status = match ($tab) {
      'scheduled' => AppointmentStatus::Scheduled,
      'history' => null, // completed + rejected
      'rejected' => AppointmentStatus::Rejected,
      default => AppointmentStatus::Pending,
    };

    $query = Appointment::with('student')
      ->orderBy('datetime', 'asc');

    if ($tab === 'history') {
      $query->whereIn('status', [
        AppointmentStatus::Completed->value,
        AppointmentStatus::Rejected->value,
      ]);
    } else {
      $query->where('status', $status->value);
    }

    return $query->get()->map(fn(Appointment $a) => $this->toRow($a));
  }

  public function tabCounts(): array
  {
    $counts = Appointment::selectRaw('status, count(*) as total')
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

  public function availableSlots(): Collection
  {
    return AvailableSchedule::whereIsTaken(false)
      ->orderBy('datetime')
      ->get()
      ->map(fn(AvailableSchedule $s) => [
        'id' => $s->id,
        'date' => $s->datetime->format('M d, Y'),
        'startTime' => $s->datetime->format('h:i A'),
      ]);
  }

  public function addSlot(string $datetime): void
  {
    AvailableSchedule::create([
      'datetime' => $datetime,
      'isTaken' => false,
    ]);
  }

  public function deleteSlot(AvailableSchedule $slot): void
  {
    $slot->delete();
  }

  private function toRow(Appointment $a): array
  {
    $student = $a->student;

    return [
      'id' => $a->id,
      'studentName' => $student?->name ?? 'Unknown',
      'context' => $a->context,
      'note' => $a->note,
      'date' => $a->datetime->format('M d, Y'),
      'time' => $a->datetime->format('h:i A'),
      'status' => $a->status->label(),
      'studentProfile' => [
        'initials' => $this->initials($student?->name ?? ''),
        'section' => $student?->section ?? '',
        'course' => '',
        'yearLevel' => $student?->year_level ?? '',
        'email' => '',
        'studentId' => $student?->student_number ?? '',
        'totalAppointments' => Appointment::where('student_id', $a->student_id)->count(),
        'history' => $this->studentHistory($a->student_id),
      ],
    ];
  }

  private function studentHistory(int $studentId): array
  {
    return Appointment::where('student_id', $studentId)
      ->orderBy('datetime', 'desc')
      ->get()
      ->map(fn(Appointment $a) => [
        'context' => $a->context,
        'date' => $a->datetime->format('M d, Y'),
        'time' => $a->datetime->format('h:i A'),
        'note' => $a->note,
        'status' => $a->status->label(),
      ])
      ->toArray();
  }

  private function initials(string $name): string
  {
    $parts = explode(' ', trim($name));
    return strtoupper(
      collect($parts)->map(fn($p) => $p[0] ?? '')->take(2)->implode('')
    );
  }
}