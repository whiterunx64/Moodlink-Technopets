<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Notification;
use App\Models\Student;
use App\Support\PhTime;
use App\Mail\ApproveAppointmentMailable;
use App\Mail\ConsultationAppointmentMailable;
use App\Mail\ReminderAppointmentMailable;
use App\Mail\RejectAppointmentMailable;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
  public function __construct(
    private readonly StudentMailer $mailer,
  ) {
  }

  // ─────────────────────────────────────────────────────────────
  // Approve / Reject / Complete
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when the appointment is not pending, or its slot is
   * missing or already booked by someone else.
   */
  public function approve(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeApproved($appointment);

    try {
      DB::transaction(function () use ($appointment): void {
        $slot = $this->findSlotForAppointment($appointment);
        $this->ensureSlotCanBeBooked($slot, $appointment);
        $this->ensureStudentHasNoOtherBookedSlot($appointment, $slot);
        $slot->update(['takenBy' => $appointment->student_id]);
        $appointment->update(['status' => AppointmentStatus::Scheduled->value]);
      });
    } catch (UniqueConstraintViolationException) {
      // Two approvals for the same student slipped past the guard at once;
      throw AppointmentException::studentAlreadyHasBookedSlot();
    }

    $student = $appointment->student;

    if ($student !== null) {
      $this->notifyStudentOfAppointmentApproved($student, $appointment);
      $this->sendStudentScheduledEmail($student, $appointment);
    }
  }

  /**
   * @throws AppointmentException when the appointment is not in Pending status.
   */
  public function reject(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeRejected($appointment);

    $appointment->update(['status' => AppointmentStatus::Rejected->value]);

    $student = $appointment->student;

    if ($student !== null) {
      $this->notifyStudentOfAppointmentRejected($student, $appointment);
      $this->sendStudentRejectedEmail($student, $appointment);
    }
  }

  /**
   * @throws AppointmentException when the appointment is not in Scheduled status.
   */
  public function complete(Appointment $appointment): void
  {
    $this->ensureAppointmentCanBeCompleted($appointment);

    $appointment->update(['status' => AppointmentStatus::Completed->value]);

    AvailableSchedule::where('takenBy', $appointment->student_id)->delete();
  }

  /**
   * @throws AppointmentException when outside the check-in window or not Scheduled.
   */
  public function completeViaCheckIn(Appointment $appointment): void
  {
    $this->ensureWithinCheckInWindow($appointment);

    $this->complete($appointment);

    Notification::deleteWithMarker('appointment_awaiting_checkin', $this->awaitingMarker($appointment));
    $this->notifyAdminSessionCompleted($appointment);
  }

  // ─────────────────────────────────────────────────────────────
  // Check-in
  // ─────────────────────────────────────────────────────────────

  public function flagSessionsAwaitingCheckIn(): int
  {
    $created = 0;

    foreach (Appointment::awaitingCheckIn()->with('student')->get() as $appointment) {
      if (!Notification::existsWithMarker('appointment_awaiting_checkin', $this->awaitingMarker($appointment))) {
        $this->notifyAdminSessionAwaitingCheckIn($appointment);

        $student = $appointment->student;
        if ($student !== null) {
          $this->notifyStudentSessionAwaitingCheckIn($student, $appointment);
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
      Notification::deleteWithMarker('appointment_awaiting_checkin', $this->awaitingMarker($appointment));

      $student = $appointment->student;
      if ($student !== null) {
        $this->notifyStudentOfSessionNotAttended($student, $appointment);
      }
    }

    return $sessions->count();
  }

  /**
   * Confirm completion to students once their session's assumed end time has passed.
   * Idempotent: a per-appointment marker on the notification prevents repeat alerts.
   */
  public function notifyCompletedSessions(): int
  {
    $notified = 0;

    foreach (Appointment::completedSessionEnded()->with('student')->get() as $appointment) {
      if (Notification::existsWithMarker('appointment_completed', $this->awaitingMarker($appointment))) {
        continue;
      }

      $student = $appointment->student;
      if ($student !== null) {
        $this->notifyStudentOfSessionCompleted($student, $appointment);
        $notified++;
      }
    }

    return $notified;
  }

  /**
   * Email students whose scheduled session begins in about an hour. The one-minute
   * reminder window (see Appointment::reminderDue) sends each reminder exactly once.
   */
  public function sendUpcomingSessionReminders(): int
  {
    $sent = 0;

    foreach (Appointment::reminderDue()->with('student')->get() as $appointment) {
      $student = $appointment->student;
      if ($student !== null) {
        $this->sendStudentReminderEmail($student, $appointment);
        $sent++;
      }
    }

    return $sent;
  }

  public function isWithinCheckInWindow(Appointment $appointment): bool
  {
    if ($appointment->status !== AppointmentStatus::Scheduled) {
      return false;
    }

    $now = PhTime::nowUtc();

    return $now->greaterThanOrEqualTo($appointment->datetime)
      && $now->lessThanOrEqualTo($this->checkInWindowEnd($appointment));
  }

  /** End of the check-in window — used as the signed-URL expiry, enforcing the upper bound. */
  public function checkInWindowEnd(Appointment $appointment): Carbon
  {
    return $appointment->datetime->copy()->addMinutes(Appointment::CHECK_IN_GRACE_MINUTES);
  }

  // ─────────────────────────────────────────────────────────────
  // Consultation
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when the student already has an open (pending or scheduled) consultation.                    
   */
  public function scheduleConsultationForStudent(Student $student, string $scheduledAt): void
  {
    $this->ensureStudentHasNoOpenConsultation($student);

    $datetime = PhTime::toUtc($scheduledAt);

    $appointment = DB::transaction(function () use ($student, $datetime): Appointment {
      $appointment = Appointment::create([
        'student_id' => $student->id,
        'context' => '🚨At-risk follow-up',
        'status' => AppointmentStatus::Scheduled->value,
        'datetime' => $datetime,
      ]);

      $student->update(['risk_start_date' => null]);

      $this->notifyStudentOfConsultation($student, $appointment);

      return $appointment;
    });

    // Email the student only after the consultation has committed.
    $this->sendStudentConsultationCreatedByAdminEmail($student, $appointment);
  }

  // ─────────────────────────────────────────────────────────────
  // Slot Management
  // ─────────────────────────────────────────────────────────────

  /**
   * @throws AppointmentException when a slot already exists at the same datetime,
   * the datetime is in the past, or falls outside GCU operating hours.
   */
  public function addSlot(string $datetime): void
  {
    $slotAt = PhTime::toUtc($datetime);
    $phTime = PhTime::fromUtc($slotAt);

    $this->ensureSlotIsNotInThePast($phTime);
    $this->ensureSlotIsWithinWorkingHours($phTime);
    $this->ensureNoSlotExistsAtSameTime($slotAt);

    AvailableSchedule::create(['datetime' => $slotAt]);
  }

  /**
   * @throws AppointmentException when the slot is already booked.
   */
  public function deleteSlot(AvailableSchedule $slot): void
  {
    $this->ensureSlotIsNotAlreadyBooked($slot);

    $slot->delete();
  }

  // ─────────────────────────────────────────────────────────────
  // Appointment Guards
  // ─────────────────────────────────────────────────────────────

  private function ensureStudentHasNoOpenConsultation(Student $student): void
  {
    $hasOpenConsultation = Appointment::where('student_id', $student->id)
      ->whereIn('status', [
        AppointmentStatus::Pending->value,
        AppointmentStatus::Scheduled->value,
      ])
      ->exists();

    if ($hasOpenConsultation) {
      throw AppointmentException::studentAlreadyHasOpenConsultation();
    }
  }

  private function ensureAppointmentCanBeApproved(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw AppointmentException::appointmentMustBePendingToApprove();
    }

    $phTime = PhTime::fromUtc($appointment->datetime);

    $this->ensureSlotIsNotInThePast($phTime);
    $this->ensureSlotIsWithinWorkingHours($phTime);
  }

  private function ensureAppointmentCanBeRejected(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Pending) {
      throw AppointmentException::appointmentMustBePendingToReject();
    }
  }

  private function ensureAppointmentCanBeCompleted(Appointment $appointment): void
  {
    if ($appointment->status !== AppointmentStatus::Scheduled) {
      throw AppointmentException::appointmentMustBeScheduledToComplete();
    }
  }
  private function ensureWithinCheckInWindow(Appointment $appointment): void
  {
    $now = PhTime::nowUtc();

    if (
      $now->lessThan($appointment->datetime) ||
      $now->greaterThan($this->checkInWindowEnd($appointment))
    ) {
      throw AppointmentException::appointmentCheckInOutsideWindow();
    }
  }

  // ─────────────────────────────────────────────────────────────
  // Slot Guards
  // ─────────────────────────────────────────────────────────────

  private function ensureNoSlotExistsAtSameTime(Carbon $datetime): void
  {
    $alreadyExists = AvailableSchedule::where('datetime', $datetime)->exists();

    if ($alreadyExists) {
      throw AppointmentException::slotAlreadyExistsAtTime();
    }
  }

  private function ensureSlotIsNotAlreadyBooked(AvailableSchedule $slot): void
  {
    if ($slot->takenBy !== null) {
      throw AppointmentException::bookedSlotCannotBeDeleted();
    }
  }

  private function ensureSlotCanBeBooked(?AvailableSchedule $slot, Appointment $appointment): void
  {
    if ($slot === null) {
      throw AppointmentException::noAvailableSlotForAppointmentTime();
    }

    if ($slot->takenBy !== null && $slot->takenBy !== $appointment->student_id) {
      throw AppointmentException::slotAlreadyBookedByAnotherStudent();
    }
  }

  private function ensureStudentHasNoOtherBookedSlot(Appointment $appointment, AvailableSchedule $slot): void
  {
    $holdsAnotherSlot = AvailableSchedule::where('takenBy', $appointment->student_id)
      ->where('id', '!=', $slot->id)
      ->exists();

    if ($holdsAnotherSlot) {
      throw AppointmentException::studentAlreadyHasBookedSlot();
    }
  }

  private function ensureSlotIsNotInThePast(Carbon $phTime): void
  {
    if ($phTime->isPast()) {
      throw AppointmentException::slotDateIsInThePast();
    }
  }

  private function ensureSlotIsWithinWorkingHours(Carbon $phTime): void
  {
    $minutes = $phTime->hour * 60 + $phTime->minute;

    // 8:00 AM = 480 min, 6:00 PM = 1080 min
    if ($minutes < 480 || $minutes > 1080) {
      throw AppointmentException::slotTimeOutsideWorkingHours();
    }
  }

  // ─────────────────────────────────────────────────────────────
  // Slot Helpers
  // ─────────────────────────────────────────────────────────────

  private function findSlotForAppointment(Appointment $appointment): ?AvailableSchedule
  {
    return AvailableSchedule::whereBetween('datetime', [
      $appointment->datetime->copy()->startOfMinute(),
      $appointment->datetime->copy()->endOfMinute(),
    ])->lockForUpdate()->first();
  }

  // ─────────────────────────────────────────────────────────────
  // Admin Notifications
  // ─────────────────────────────────────────────────────────────

  private function notifyAdminSessionAwaitingCheckIn(Appointment $appointment): void
  {
    Notification::adminAlert(
      'Student Has Not Checked In',
      "Student: {$appointment->student_name}\n"
      . "Date: {$appointment->display_date}\n"
      . "Time: {$appointment->display_time}\n\n"
      . "The scheduled session has already started, but the student has not checked in yet. "
      . $this->awaitingMarker($appointment),
      'appointment_awaiting_checkin',
    );
  }

  private function notifyAdminSessionCompleted(Appointment $appointment): void
  {
    Notification::adminAlert(
      'Session Completed',
      "Student: {$appointment->student_name}\n"
      . "Date: {$appointment->display_date}\n"
      . "Time: {$appointment->display_time}\n\n"
      . "The student checked in and the session has been completed.",
      'appointment_completed',
    );
  }

  private function awaitingMarker(Appointment $appointment): string
  {
    return "[Ref:{$appointment->id}]";
  }

  // ─────────────────────────────────────────────────────────────
  // Student Notifications
  // ─────────────────────────────────────────────────────────────

  private function notifyStudentOfConsultation(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '🗓️ A Consultation Has Been Arranged for You',
      "A consultation has been scheduled for you on {$appointment->display_date} at {$appointment->display_time}. Please know that this is a safe space where you can share your concerns and receive support from the Guidance & Counseling Unit. We look forward to meeting with you.",
      'set_appointment',
    );
  }

  private function notifyStudentOfAppointmentApproved(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '✅ Appointment Approved by Guidance Office',
      "Your appointment request has been approved and scheduled for {$appointment->display_date} at {$appointment->display_time}. Please attend your session as scheduled. If you are unable to attend or the scheduled date and time conflicts with your availability, please check the personal email address used for this application for our contact information.",
      'approve_appointment',
    );
  }

  private function notifyStudentOfAppointmentRejected(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      'ℹ️ Appointment Rejected by Guidance Office',
      "Your appointment request could not be approved due to scheduling conflicts, availability, or other considerations. Kindly check the personal email address used for this application for further information or our contact details.",
      'appointment_rejected',
    );
  }

  private function notifyStudentSessionAwaitingCheckIn(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '💬 Your Counseling Session Is Ready for Check-In',
      "Your session scheduled on {$appointment->display_date} at {$appointment->display_time} has started. Please proceed to the Guidance & Counseling Unit and present yourself so the counselor can check you in. You have " . Appointment::CHECK_IN_GRACE_MINUTES . " minutes from the start time before the session is marked as missed.",
      'session_ready_for_check_in',
    );
  }

  private function notifyStudentOfSessionCompleted(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '🤝 Thank You for Attending Your Session',
      "Your session scheduled on {$appointment->display_date} at {$appointment->display_time} has been completed. Thank you for taking the time to meet with the Guidance & Counseling Unit. We appreciate your trust and participation. If you are facing personal concerns, emotional difficulties, or simply need someone to talk to, you are welcome to set another appointment with us. A conversation with a counselor may help you better understand and manage your situation.",
      'session_completed',
    );
  }

  private function notifyStudentOfSessionNotAttended(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '⌛ Scheduled Session Not Attended',
      "We noticed that you were unable to attend your session scheduled on {$appointment->display_date} at {$appointment->display_time}. The session has been marked as missed because no check-in was recorded within " . Appointment::CHECK_IN_GRACE_MINUTES . " minutes of the scheduled start time. If something prevented you from attending or you are going through any concerns, you are welcome to set another appointment with the Guidance & Counseling Unit. We are here to listen and support you.",
      'session_missed',
    );
  }

  // ─────────────────────────────────────────────────────────────
  // Emails
  // ─────────────────────────────────────────────────────────────

  private function sendStudentScheduledEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new ApproveAppointmentMailable($student, $appointment),
      'appointment-scheduled',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function sendStudentReminderEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new ReminderAppointmentMailable($student, $appointment),
      'appointment-reminder',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function sendStudentRejectedEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new RejectAppointmentMailable($student, $appointment),
      'appointment-rejected',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function sendStudentConsultationCreatedByAdminEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new ConsultationAppointmentMailable($student, $appointment),
      'consultation-created-by-admin',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }
}