<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Exceptions\MailDeliveryException;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Notification;
use App\Models\Student;
use App\Support\PhTime;
use App\Mail\ApproveAppointmentMailable;
use App\Mail\ConsultationAppointmentMailable;
use App\Mail\RejectAppointmentMailable;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AppointmentService
{
  // ─────────────────────────────────────────────────────────────
  // Application Service method (approve / reject / complete)
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

  // ─────────────────────────────────────────────────────────────
  // System-created consultation scheduling
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

      // Consultation is an At Risk exit: clear the sticky flag so the student
      // leaves the list. The active consultation also blocks re-flagging.
      $student->update(['risk_start_date' => null]);

      $this->notifyStudentOfConsultation($student, $appointment);

      return $appointment;
    });

    // Email the student only after the consultation has committed.
    $this->sendStudentConsultationCreatedByAdminEmail($student, $appointment);
  }

  // ─────────────────────────────────────────────────────────────
  // Slot management (create / delete / lookup)
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

  private function findSlotForAppointment(Appointment $appointment): ?AvailableSchedule
  {
    return AvailableSchedule::whereBetween('datetime', [
      $appointment->datetime->copy()->startOfMinute(),
      $appointment->datetime->copy()->endOfMinute(),
    ])->lockForUpdate()->first();
  }

  // ─────────────────────────────────────────────────────────────
  // Internal notifications (in-app only)
  // ─────────────────────────────────────────────────────────────

  private function notifyStudentOfConsultation(Student $student, Appointment $appointment): void
  {
    Notification::create([
      'student_id' => $student->id,
      'title' => 'Consultation Scheduled by Guidance Office',
      'content' => "The guidance office set a consultation appointment with you on {$appointment->display_date} at {$appointment->display_time}. Please attend so we can support you.",
      'type' => 'set_appointment',
      'is_seen' => DB::raw('false'),
      'datetime' => Carbon::now(),
    ]);
  }

  private function notifyStudentOfAppointmentApproved(Student $student, Appointment $appointment): void
  {
    Notification::create([
      'student_id' => $student->id,
      'title' => 'Appointment Approved by Guidance Office',
      'content' => "Your appointment request has been approved and scheduled on {$appointment->display_date} at {$appointment->display_time}. Please make sure to attend. If you are unavailable, kindly contact the Guidance & Counseling Unit to reschedule.",
      'type' => 'approve_appointment',
      'is_seen' => DB::raw('false'),
      'datetime' => Carbon::now(),
    ]);
  }
  private function notifyStudentOfAppointmentRejected(Student $student, Appointment $appointment): void
  {
    Notification::create([
      'student_id' => $student->id,
      'title' => 'Appointment Rejected by Guidance Office',
      'content' => "Your appointment request could not be approved due to scheduling conflicts, availability, or other considerations. Please submit a new request or contact the Guidance & Counseling Unit for assistance.",
      'type' => 'appointment_rejected',
      'is_seen' => DB::raw('false'),
      'datetime' => Carbon::now(),
    ]);
  }
  // ─────────────────────────────────────────────────────────────
  // Ping the student via Personal Email SMTP 
  // ─────────────────────────────────────────────────────────────

  private function sendStudentScheduledEmail(Student $student, Appointment $appointment): void
  {
    $this->deliverEmailToStudent(
      $student,
      new ApproveAppointmentMailable($student, $appointment),
      'appointment-scheduled',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function sendStudentRejectedEmail(Student $student, Appointment $appointment): void
  {
    $this->deliverEmailToStudent(
      $student,
      new RejectAppointmentMailable($student, $appointment),
      'appointment-rejected',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function sendStudentConsultationCreatedByAdminEmail(Student $student, Appointment $appointment): void
  {
    $this->deliverEmailToStudent(
      $student,
      new ConsultationAppointmentMailable($student, $appointment),
      'consultation-created-by-admin',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  private function deliverEmailToStudent(Student $student, Mailable $mailable, string $type, array $context = []): void
  {
    $context = ['student_id' => $student->id, 'email_type' => $type] + $context;

    if (!$student->personal_email) {
      Log::channel('mail')->warning('Cannot send email: student has no personal email address on file', $context);
      return;
    }

    $context['recipient_email'] = $student->personal_email;

    try {
      Mail::to($student->personal_email)->send($mailable);

      Log::channel('mail')->info('mail.sent', $context);
    } catch (TransportExceptionInterface $e) {
      $failure = MailDeliveryException::from($e);

      Log::channel('mail')->error("mail.failed reason={$failure->reason()} {$failure->summary()}", $context + [
        'reason' => $failure->reason(),
        'detail' => $failure->detail(),
      ]);
    }
  }

  // ─────────────────────────────────────────────────────────────
  // Appointment exception guard logic 
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

  // ─────────────────────────────────────────────────────────────
  // Slot constraints (prevent overlap / double booking)
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
}