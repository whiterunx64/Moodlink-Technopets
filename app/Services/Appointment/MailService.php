<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Mail\ConsultationAppointmentMailable;
use App\Mail\ReminderAppointmentMailable;
use App\Models\Appointment;
use App\Models\Student;
use App\Services\StudentMailer;
class MailService
{
  public function __construct(
    private readonly StudentMailer $mailer,
  ) {
  }

  public function sendStudentReminderEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new ReminderAppointmentMailable($student, $appointment),
      'appointment-reminder',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }

  public function sendStudentConsultationCreatedByAdminEmail(Student $student, Appointment $appointment): void
  {
    $this->mailer->send(
      $student,
      new ConsultationAppointmentMailable($student, $appointment),
      'consultation-created-by-admin',
      ['appointment_id' => $appointment->id, 'datetime' => $appointment->datetime],
    );
  }
}