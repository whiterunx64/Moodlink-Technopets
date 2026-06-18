<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\YearLevel;
use App\Models\Appointment;
use App\Models\Student;
use Illuminate\Support\Collection;

trait HasStudentDisplay
{
  private function displayStudentName(): string
  {
    if ($this->student === null) {
      return 'Unknown';
    }

    return $this->student->name;
  }

  private function displayStudentSection(): string
  {
    if ($this->student === null) {
      return '';
    }

    return $this->student->section;
  }

  private function studentProfile(): array
  {
    if ($this->student === null) {
      return [
        'initials' => '',
        'section' => '',
        'year_level' => '',
        'student_id' => '',
        'total_appointments' => 0,
        'history' => [],
      ];
    }

    $appointments = $this->student->appointments;

    return [
      'initials' => $this->getInitialsFromName($this->student->name),
      'section' => $this->student->section,
      'year_level' => YearLevel::tryFrom($this->student->year_level)?->toOrdinal() ?? '',
      'student_id' => $this->student->student_number,
      'total_appointments' => $appointments->count(),
      'history' => $this->appointmentHistory($appointments),
    ];
  }

  private function getInitialsFromName(string $name): string
  {
    $words = explode(' ', trim($name));

    $initials = collect($words)
      ->map(fn(string $word) => $word[0] ?? '')
      ->take(2)
      ->implode('');

    return strtoupper($initials);
  }

  /**
   * @param Collection<int, Appointment> $appointments
   * @return array<int, array<string, mixed>>
   */
  private function appointmentHistory(Collection $appointments): array
  {
    return $appointments
      ->sortByDesc('datetime')
      ->map(fn(Appointment $appointment): array => [
        'context' => $appointment->context,
        'date' => $appointment->displayDate(),
        'time' => $appointment->displayTime(),
        'note' => $appointment->note,
        'status' => $appointment->status->value,
      ])
      ->values()
      ->toArray();
  }
}