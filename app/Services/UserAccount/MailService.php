<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Mail\AccountDeletionMailable;
use App\Mail\InitialPasswordMailable;
use App\Models\Student;
use App\Services\StudentMailer;

class MailService
{
    public function __construct(
        private readonly StudentMailer $mailer,
    ) {
    }

    public function sendInitialPasswordToPersonalEmail(Student $student, string $email, string $password): void
    {
        $this->mailer->send(
            $student,
            new InitialPasswordMailable($student, $email, $password),
            'student-initial-password',
        );
    }

    public function sendAccountDeletionNotice(Student $student): void
    {
        $this->mailer->send(
            $student,
            new AccountDeletionMailable($student),
            'student-account-deletion',
        );
    }
}