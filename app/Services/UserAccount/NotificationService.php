<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Models\Notification;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function notifyStudentToChangeInitialPassword(Student $student): void
    {
        try {
            Notification::studentAlert(
                $student->id,
                'Initial Password Change Required',
                'Your account is currently using an initial password. For your account security, please change your password to a secure, unique one that only you know in order to protect your account. Changing your original password lowers the possibility of account compromise, credential exposure, and unauthorized access.',
                'security_alert',
            );
        } catch (Throwable $e) {
            // Don't fail account creation if the notification cannot be stored.
            Log::warning('Failed to create initial password notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
