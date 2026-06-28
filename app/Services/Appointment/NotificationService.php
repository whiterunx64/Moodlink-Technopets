<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Student;
class NotificationService
{
  public function awaitingMarker(Appointment $appointment): string
  {
    return "[Ref:{$appointment->id}]";
  }

  // ─────────────────────────────────────────────────────────────
  // Admin Notifications
  // ─────────────────────────────────────────────────────────────

  public function notifyAdminSessionAwaitingCheckIn(Appointment $appointment): void
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

  public function notifyAdminSessionCompleted(Appointment $appointment): void
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

  // ─────────────────────────────────────────────────────────────
  // Student Notifications
  // ─────────────────────────────────────────────────────────────

  public function notifyStudentOfConsultation(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '🗓️ A Consultation Has Been Arranged for You',
      "A consultation has been scheduled for you on {$appointment->display_date} at {$appointment->display_time}. Please know that this is a safe space where you can share your concerns and receive support from the Guidance & Counseling Unit. We look forward to meeting with you.",
      'set_appointment',
    );
  }

  public function notifyStudentOfAppointmentApproved(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '✅ Appointment Approved by Guidance Office',
      "Your appointment request has been approved and scheduled for {$appointment->display_date} at {$appointment->display_time}. Please attend your session as scheduled. If you are unable to attend or the scheduled date and time conflicts with your availability, please check the personal email address used for this application for our contact information.",
      'approve_appointment',
    );
  }

  public function notifyStudentOfAppointmentRejected(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      'ℹ️ Appointment Rejected by Guidance Office',
      "Your appointment request could not be approved due to scheduling conflicts, availability, or other considerations. Kindly check the personal email address used for this application for further information or our contact details.",
      'appointment_rejected',
    );
  }

  public function notifyStudentSessionAwaitingCheckIn(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '💬 Your Counseling Session Is Ready for Check-In',
      "Your session scheduled on {$appointment->display_date} at {$appointment->display_time} has started. Please proceed to the Guidance & Counseling Unit and present yourself so the counselor can check you in. You have " . Appointment::CHECK_IN_GRACE_MINUTES . " minutes from the start time before the session is marked as missed.",
      'session_ready_for_check_in',
    );
  }

  public function notifyStudentOfSessionCompleted(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '🤝 Thank You for Attending Your Session',
      "Your session scheduled on {$appointment->display_date} at {$appointment->display_time} has been completed. Thank you for taking the time to meet with the Guidance & Counseling Unit. We appreciate your trust and participation. If you are facing personal concerns, emotional difficulties, or simply need someone to talk to, you are welcome to set another appointment with us. A conversation with a counselor may help you better understand and manage your situation.\n\n" . $this->awaitingMarker($appointment),
      'session_completed',
    );
  }

  public function notifyStudentOfSessionNotAttended(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '⌛ Scheduled Session Not Attended',
      "We noticed that you were unable to attend your session scheduled on {$appointment->display_date} at {$appointment->display_time}. The session has been marked as missed because no check-in was recorded within " . Appointment::CHECK_IN_GRACE_MINUTES . " minutes of the scheduled start time. If something prevented you from attending or you are going through any concerns, you are welcome to set another appointment with the Guidance & Counseling Unit. We are here to listen and support you.",
      'session_missed',
    );
  }

  public function notifyStudentOfUpcomingSession(Student $student, Appointment $appointment): void
  {
    Notification::studentAlert(
      $student->id,
      '⏰ Reminder: Your Counseling Session Is Coming Up',
      "This is a reminder that your session is scheduled on {$appointment->display_date} at {$appointment->display_time}, about an hour from now. Please make sure to arrive on time at the Guidance & Counseling Unit.\n\n" . $this->awaitingMarker($appointment),
      'appointment_reminder',
    );
  }
}