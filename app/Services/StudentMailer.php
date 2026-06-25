<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MailDeliveryException;
use App\Models\Student;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class StudentMailer
{
    /**
     * @param array<string, mixed> $context Extra structured log context (e.g. appointment_id).
     */
    public function send(Student $student, Mailable $mailable, string $type, array $context = []): void
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
        } catch (Throwable $e) {
            Log::channel('mail')->warning('mail.failed', $context + ['error' => $e->getMessage()]);
        }
    }
}