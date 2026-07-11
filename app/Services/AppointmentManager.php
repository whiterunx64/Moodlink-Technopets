<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Notification;
use App\Models\Student;
use App\Services\Appointment\QueryService;
use App\Services\Appointment\Validator;
use App\Services\Appointment\MailService;
use App\Services\Appointment\NotificationService;
use App\Services\Appointment\ScheduledSessionTasks;
use App\Services\Appointment\SlotManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class AppointmentManager
{
  public function __construct(
    private readonly Validator $validator,
    private readonly NotificationService $notifications,
    private readonly MailService $mail,
    private readonly SlotManager $slots,
    private readonly ScheduledSessionTasks $scheduledTasks,
    private readonly QueryService $queries,
  ) {
  }

  // ─────────────────────────────────────────────────────────────
  // Public Methods
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when the appointment is not in Scheduled status.
   */
  public function complete(Appointment $appointment): void
  {
    $this->validator->ensureAppointmentCanBeCompleted($appointment);

    $appointment->update(['status' => AppointmentStatus::Completed->value]);

    AvailableSchedule::where('takenBy', $appointment->student_id)->delete();
  }

  /**
   * @throws AppointmentException when outside the check-in window or not Scheduled.
   */
  public function completeViaCheckIn(Appointment $appointment): void
  {
    $this->validator->ensureWithinCheckInWindow($appointment);

    $this->complete($appointment);

    Notification::deleteWithMarker('appointment_awaiting_checkin', $this->notifications->awaitingMarker($appointment));
    $this->notifications->notifyAdminSessionCompleted($appointment);
  }

  // ─────────────────────────────────────────────────────────────
  // At-Risk Follow-Up Scheduling
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when the student already has an open consultation,
   * the slot is missing, in the past, or already booked by someone else.
   */
  public function scheduleConsultationForStudent(Student $student, int $slotId): void
  {
    $this->validator->ensureStudentHasNoOpenConsultation($student);

    try {
      $appointment = DB::transaction(function () use ($student, $slotId): Appointment {
        $slot = AvailableSchedule::lockForUpdate()->find($slotId);

        $this->validator->ensureConsultationSlotIsBookable($slot, $student);

        $appointment = Appointment::create([
          'student_id' => $student->id,
          'context' => '🚨At-risk follow-up',
          'status' => AppointmentStatus::Scheduled->value,
          'datetime' => $slot->datetime,
        ]);

        $slot->update(['takenBy' => $student->id]);
        $student->update(['risk_start_date' => null]);

        $this->notifications->notifyStudentOfConsultation($student, $appointment);

        return $appointment;
      });
    } catch (UniqueConstraintViolationException) {
      // Two referrals for the same student raced past the guard at once.
      throw AppointmentException::studentAlreadyHasBookedSlot();
    }

    // Email the student only after the consultation has committed.
    $this->mail->sendStudentConsultationCreatedByAdminEmail($student, $appointment);
  }

  // ─────────────────────────────────────────────────────────────
  // Scheduled session batches
  // ─────────────────────────────────────────────────────────────

  public function flagSessionsAwaitingCheckIn(): int
  {
    return $this->scheduledTasks->flagSessionsAwaitingCheckIn();
  }

  public function rejectNoShowSessions(): int
  {
    return $this->scheduledTasks->rejectNoShowSessions();
  }

  public function notifyCompletedSessions(): int
  {
    return $this->scheduledTasks->notifyCompletedSessions();
  }

  public function sendUpcomingSessionReminders(): int
  {
    return $this->scheduledTasks->sendUpcomingSessionReminders();
  }

  // ─────────────────────────────────────────────────────────────
  // Slot management
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when a slot already exists at the same datetime,
   * the datetime is in the past, or falls outside GCU operating hours.
   */
  public function addSlot(string $datetime): void
  {
    $this->slots->addSlot($datetime);
  }

  /**
   * @throws AppointmentException when the slot is already booked.
   */
  public function deleteSlot(AvailableSchedule $slot): void
  {
    $this->slots->deleteSlot($slot);
  }

  // ─────────────────────────────────────────────────────────────
  // Appointment Display Data
  // ─────────────────────────────────────────────────────────────

  /**
   * @return array<string, int>
   */
  public function tabCounts(): array
  {
    return $this->queries->tabCounts();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function appointmentListForTab(string $tab): array
  {
    return $this->queries->appointmentListForTab($tab);
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function availableSlotsList(): array
  {
    return $this->queries->availableSlotsList();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function openConsultationSlots(): array
  {
    return $this->queries->openConsultationSlots();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function checkInReadyList(): array
  {
    return $this->queries->checkInReadyList();
  }

  /**
   * @return array<string, mixed>
   */
  public function studentProfileWithHistory(Student $student): array
  {
    return $this->queries->studentProfileWithHistory($student);
  }
}