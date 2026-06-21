<?php

namespace App\Services;

use App\Contracts\SupabaseAuthInterface;
use App\Enums\StudentStatus;
use App\Models\Notification;
use App\Models\Student;
use App\Mail\StudentCredentialsMail;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

use function strlen;
use function count;

final class StudentAccountService
{
    public function __construct(
        private readonly SupabaseAuthInterface $supabase,
    ) {
    }

    /**
     * @throws DomainException when the student is not Pending or Unverified.
     */
    public function verifyStudent(Student $student): void
    {
        $this->ensureStudentCanBeVerified($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * Reject a student's registration by permanently deleting their record.
     *
     * Only students still awaiting verification (Pending) may be rejected.
     * Verified or suspended students have active accounts and must be
     * suspended instead, never deleted.
     *
     * @throws DomainException when the student is not in a rejectable state.
     */
    public function rejectStudent(Student $student): void
    {
        $this->ensureStudentCanBeRejected($student);

        $student->delete();
    }

    /**
     * @throws DomainException when the student is not Verified.
     */
    public function suspendStudent(Student $student): void
    {
        $this->ensureStudentCanBeSuspended($student);

        $student->update(['status' => StudentStatus::Suspended->value]);
    }

    /**
     * @throws DomainException when the student is not Suspended.
     */
    public function reactivateStudent(Student $student): void
    {
        $this->ensureStudentCanBeReactivated($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * @return array{email: string, password: string}
     *
     * @throws DomainException when the student is not Pending.
     * @throws \Exception when Supabase signup fails.
     */
    public function createSupabaseAccountForStudent(Student $student): array
    {
        $this->ensureStudentIsEligibleForRegistration($student);

        $email = "{$student->student_number}@moodlink.com";
        $password = $this->generateInitialPassword();

        $response = $this->supabase->createStudentAccountApiCall(
            email: $email,
            password: $password,
            data: [
                'student_id' => $student->id,
                'name' => $student->name,
            ],
            emailConfirm: true,
        );

        // Persist the Supabase user id so the account can be deleted later.
        $student->update(['user_id' => $response['id'] ?? null]);

        $this->verifyStudent($student);

        // Email the crenditals to the student's personal email
        $this->sendInitialPasswordToPersonalEmail($student, $email, $password);

        // Prompt the student to change their initial password on first login.
        $this->notifyStudentToChangeInitialPassword($student);

        return ['email' => $email, 'password' => $password];
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function ensureStudentCanBeVerified(Student $student): void
    {
        $isEligible = $student->status === StudentStatus::Pending
            || $student->status === StudentStatus::Unverified;

        if (!$isEligible) {
            throw new DomainException('Only pending or unverified students can be verified.');
        }
    }


    private function ensureStudentCanBeSuspended(Student $student): void
    {
        if ($student->status !== StudentStatus::Verified) {
            throw new DomainException('Only verified students can be suspended.');
        }
    }

    private function ensureStudentCanBeReactivated(Student $student): void
    {
        if ($student->status !== StudentStatus::Suspended) {
            throw new DomainException('Only suspended students can be reactivate.');
        }
    }

    private function ensureStudentIsEligibleForRegistration(Student $student): void
    {
        if ($student->status !== StudentStatus::Pending) {
            throw new DomainException('Only pending students can be registered.');
        }
    }

    private function ensureStudentCanBeRejected(Student $student): void
    {
        if ($student->user_id !== null) {
            throw new DomainException('This student has an active account and cannot be rejected. Suspend it instead.');
        }

        if ($student->status !== StudentStatus::Pending) {
            throw new DomainException('Only pending students can be rejected.');
        }
    }

    private function sendInitialPasswordToPersonalEmail(Student $student, string $email, string $password): void
    {
        if (!$student->personal_email) {
            return;
        }

        try {
            Mail::to($student->personal_email)
                ->send(new StudentCredentialsMail($student, $email, $password));
        } catch (Throwable $e) {
            // Don't fail account creation if email delivery fails
            Log::warning('Failed to email student crendetials', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyStudentToChangeInitialPassword(Student $student): void
    {
        try {
            Notification::create([
                'student_id' => $student->id,
                'title' => 'Initial Password Change Required',
                'content' => 'Your account is currently using an initial password. For your account security, please change your password to a secure, unique one that only you know in order to protect your account. Changing your original password lowers the possibility of account compromise, credential exposure, and unauthorized access.',
                'type' => 'security_alert',
                'is_seen' => false,
                'datetime' => now(),
            ]);
        } catch (Throwable $e) {
            // Don't fail account creation if the notification cannot be stored.
            Log::warning('Failed to create initial password notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateInitialPassword(int $length = 8): string
    {
        // random 8 character one lowercase letter, maximum two uppercase letters, and one symbol.
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $symbols = '!@$^-';

        $all = $lower;

        $chars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
        ];

        // Add remaining characters 
        while (count($chars) < $length - 1) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle all variables
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];

        return implode('', $chars) . $symbol;
    }
}