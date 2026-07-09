<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\YearLevel;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Student;
use App\Support\PhTime;
use Illuminate\Support\Facades\URL;

class QueryService
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public function appointmentListForTab(string $tab): array
  {
    $status = AppointmentStatus::fromTab($tab);
    $now = PhTime::nowUtc();

    $showsUpcoming = $tab === 'requests' || $tab === 'scheduled';

    return Appointment::query()
      ->with(['student' => fn($q) => $q->withCount('appointments')->withCasts(['appointments_count' => 'integer'])])
      ->where('status', $status->value)
      ->when($tab === 'scheduled', fn($q) => $q->where('datetime', '>=', $now->copy()->subMinutes(Appointment::CHECK_IN_GRACE_MINUTES)))
      ->when($tab === 'missed', fn($q) => $q->where('datetime', '<', $now))
      ->orderBy('datetime', $showsUpcoming ? 'asc' : 'desc')
      ->get()
      ->map(fn(Appointment $appointment): array => [
        'id' => $appointment->id,
        'student_id' => $appointment->student_id,
        'context' => $appointment->context,
        'note' => $appointment->note,
        'status' => $appointment->status->value,
        'date' => $appointment->display_date,
        'time' => $appointment->display_time,
        'student_name' => $appointment->student_name,
        'program' => $appointment->student_program,
        'student_summary' => $this->buildStudentSummary($appointment),
        'can_check_in' => $appointment->isWithinCheckInWindow(),
        'checkin_url' => $this->checkInUrl($appointment),
        'checkin_expires_at' => $appointment->checkInWindowEnd()->toIso8601String(),
      ])
      ->all();
  }

  /**
   * @return array<string, mixed>
   */
  public function studentProfileWithHistory(Student $student): array
  {
    $student->loadCount('appointments');
    $student->load(['appointments' => fn($q) => $q->orderByDesc('datetime')]);

    return [
      'name' => $student->name,
      'initials' => $student->studentNameInitials,
      'program' => $student->program,
      'year_level' => YearLevel::tryFrom($student->year_level)?->toOrdinal() ?? '',
      'student_id' => $student->student_number,
      'total_appointments' => $student->appointments_count ?? 0,
      'history' => $student->appointments
        ->map(fn(Appointment $historyItem): array => [
          'context' => $historyItem->context,
          'date' => $historyItem->display_date,
          'time' => $historyItem->display_time,
          'note' => $historyItem->note,
          'status' => $historyItem->status->value,
        ])
        ->values()
        ->all(),
    ];
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function availableSlotsList(): array
  {
    return AvailableSchedule::query()
      ->where('datetime', '>=', PhTime::now()->utc())
      ->orderBy('datetime')
      ->get()
      ->map(fn(AvailableSchedule $schedule): array => [
        'id' => $schedule->id,
        'date' => $schedule->display_date,
        'start_time' => $schedule->display_time,
        'taken' => $schedule->takenBy !== null,
      ])
      ->all();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function openConsultationSlots(): array
  {
    return AvailableSchedule::query()
      ->whereNull('takenBy')
      ->where('datetime', '>=', PhTime::now()->utc())
      ->orderBy('datetime')
      ->get()
      ->map(fn(AvailableSchedule $schedule): array => [
        'id' => $schedule->id,
        'date' => $schedule->display_date,
        'time' => $schedule->display_time,
      ])
      ->all();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function checkInReadyList(): array
  {
    return Appointment::awaitingCheckIn()->with('student')->get()
      ->filter(fn(Appointment $appointment): bool => $appointment->isWithinCheckInWindow())
      ->map(fn(Appointment $appointment): array => [
        'id' => $appointment->id,
        'student_name' => $appointment->student_name,
        'date' => $appointment->display_date,
        'time' => $appointment->display_time,
        'checkin_url' => $this->checkInUrl($appointment),
        'checkin_expires_at' => $appointment->checkInWindowEnd()->toIso8601String(),
      ])
      ->values()
      ->all();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function checkInAlerts(): array
  {
    return Appointment::awaitingCheckIn()->with('student')->get()
      ->filter(fn(Appointment $appointment): bool => $appointment->isWithinCheckInWindow())
      ->map(fn(Appointment $appointment): array => [
        'id' => $appointment->id,
        'student_name' => $appointment->student_name,
      ])
      ->values()
      ->all();
  }

  /**
   * @return array<string, int>
   */
  public function tabCounts(): array
  {
    return Appointment::query()
      ->selectRaw(
        'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS requests,
         COALESCE(SUM(CASE WHEN status = ? AND datetime >= ? THEN 1 ELSE 0 END), 0) AS scheduled,
         COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS history,
         COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS rejected,
         COALESCE(SUM(CASE WHEN status = ? AND datetime < NOW() THEN 1 ELSE 0 END), 0) AS missed',
        [
          AppointmentStatus::Pending->value,
          AppointmentStatus::Scheduled->value,
          PhTime::nowUtc()->subMinutes(Appointment::CHECK_IN_GRACE_MINUTES),
          AppointmentStatus::Completed->value,
          AppointmentStatus::Rejected->value,
          AppointmentStatus::Missed->value,
        ],
      )
      ->withCasts([
        'requests' => 'integer',
        'scheduled' => 'integer',
        'history' => 'integer',
        'rejected' => 'integer',
        'missed' => 'integer',
      ])
      ->first()
      ->only(['requests', 'scheduled', 'history', 'rejected', 'missed']);
  }

  private function checkInUrl(Appointment $appointment): ?string
  {
    if ($appointment->status !== AppointmentStatus::Scheduled) {
      return null;
    }

    return URL::temporarySignedRoute(
      'appointments.checkin',
      $appointment->checkInWindowEnd(),
      ['appointment' => $appointment->id],
    );
  }

  /**
   * @return array<string, mixed>
   */
  private function buildStudentSummary(Appointment $appointment): array
  {
    $student = $appointment->student;

    if ($student === null) {
      return [
        'initials' => '',
        'program' => '',
        'year_level' => '',
        'student_id' => '',
        'total_appointments' => 0,
      ];
    }

    return [
      'initials' => $student->studentNameInitials,
      'program' => $student->program,
      'year_level' => YearLevel::tryFrom($student->year_level)?->toOrdinal() ?? '',
      'student_id' => $student->student_number,
      'total_appointments' => $student->appointments_count ?? 0,
    ];
  }
}