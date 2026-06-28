<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
class ScheduledSessionTasks
{
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly MailService $mail,
  ) {
  }

  public function flagSessionsAwaitingCheckIn(): int
  {
    $created = 0;

    foreach (Appointment::awaitingCheckIn()->with('student')->get() as $appointment) {
      if (!Notification::existsWithMarker('appointment_awaiting_checkin', $this->notifications->awaitingMarker($appointment))) {
        $this->notifications->notifyAdminSessionAwaitingCheckIn($appointment);

        $student = $appointment->student;
        if ($student !== null) {
          $this->notifications->notifyStudentSessionAwaitingCheckIn($student, $appointment);
        }

        $created++;
      }
    }

    return $created;
  }

  public function rejectNoShowSessions(): int
  {
    $sessions = Appointment::missedCheckIn()->with('student')->get();

    foreach ($sessions as $appointment) {
      DB::transaction(function () use ($appointment): void {
        $appointment->update(['status' => AppointmentStatus::Missed->value]);
        AvailableSchedule::where('takenBy', $appointment->student_id)->delete();
      });

      // The session is over, so drop the now-stale awaiting-check-in alert.
      Notification::deleteWithMarker('appointment_awaiting_checkin', $this->notifications->awaitingMarker($appointment));

      $student = $appointment->student;
      if ($student !== null) {
        $this->notifications->notifyStudentOfSessionNotAttended($student, $appointment);
      }
    }

    return $sessions->count();
  }

  public function notifyCompletedSessions(): int
  {
    $notified = 0;

    foreach (Appointment::completedSessionEnded()->with('student')->get() as $appointment) {

      if (Notification::existsWithMarker('session_completed', $this->notifications->awaitingMarker($appointment))) {
        continue;
      }

      $student = $appointment->student;
      if ($student !== null) {
        $this->notifications->notifyStudentOfSessionCompleted($student, $appointment);
        $notified++;
      }
    }

    return $notified;
  }

  public function sendUpcomingSessionReminders(): int
  {
    $sent = 0;

    foreach (Appointment::reminderDue()->with('student')->get() as $appointment) {

      if (Notification::existsWithMarker('appointment_reminder', $this->notifications->awaitingMarker($appointment))) {
        continue;
      }

      $student = $appointment->student;
      if ($student !== null) {
        $this->mail->sendStudentReminderEmail($student, $appointment);
        $this->notifications->notifyStudentOfUpcomingSession($student, $appointment);
        $sent++;
      }
    }

    return $sent;
  }
}