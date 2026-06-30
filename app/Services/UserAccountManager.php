<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StudentStatus;
use App\Exceptions\CircuitBreakerException;
use App\Exceptions\StudentAccountException;
use App\Models\Student;
use App\Services\UserAccount\MailService;
use App\Services\UserAccount\NotificationService;
use App\Services\UserAccount\PasswordGenerator;
use App\Services\UserAccount\QueryService;
use App\Services\UserAccount\StudentAccountProvisioner;
use App\Services\UserAccount\Validator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserAccountManager
{
    public function __construct(
        private readonly Validator $validator,
        private readonly QueryService $queries,
        private readonly StudentAccountProvisioner $provisioner,
        private readonly NotificationService $notifications,
        private readonly PasswordGenerator $passwords,
        private readonly MailService $mail,
    ) {
    }

    // ─────────────────────────────────────────────────────────────
    // Public Method — QueryService.php
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $filters
     */
    public function paginatedStudentList(array $filters): LengthAwarePaginator
    {
        return $this->queries->paginatedStudentList($filters);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function tabCounts(array $filters): array
    {
        return $this->queries->tabCounts($filters);
    }

    // ─────────────────────────────────────────────────────────────
    // Public Method — Validator.php
    // ─────────────────────────────────────────────────────────────

    /**
     * @throws StudentAccountException when the student is not Pending or Unverified.
     */
    public function verifyStudent(Student $student): void
    {
        $this->validator->ensureStudentCanBeVerified($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * @throws StudentAccountException when the student is not in a rejectable state.
     */
    public function rejectStudent(Student $student): void
    {
        $this->validator->ensureStudentCanBeRejected($student);

        $student->delete();
    }

    /**
     * @throws StudentAccountException when the student is not Verified.
     */
    public function suspendStudent(Student $student): void
    {
        $this->validator->ensureStudentCanBeSuspended($student);

        $student->update(['status' => StudentStatus::Suspended->value]);
    }

    /**
     * @throws StudentAccountException when the student is not Suspended.
     */
    public function reactivateStudent(Student $student): void
    {
        $this->validator->ensureStudentCanBeReactivated($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    // ─────────────────────────────────────────────────────────────
    // Public Method — StudentAccountProvisioner.php
    // ─────────────────────────────────────────────────────────────

    /**
     * @throws StudentAccountException when the auth account cannot be deleted.
     */
    public function deleteStudent(Student $student): void
    {
        $this->provisioner->deleteSupabaseAccount($student);
        $this->mail->sendAccountDeletionNotice($student);
        $student->delete();
    }

    /**
     * @return array{email: string, password: string, supabase_user_id: string}
     *
     * @throws StudentAccountException when a business rule is violated or the
     *         account provider rejects the request.
     * @throws CircuitBreakerException when the account provider is unavailable.
     */
    public function registerStudent(Student $student): array
    {
        // Ensure the student meets all requirements before account creation.
        $this->validator->ensureStudentIsEligibleForRegistration($student);
        $this->validator->ensureStudentNumberUsesRequiredLength($student);
        $this->validator->ensureStudentHasValidPersonalEmail($student);

        $email = "{$student->student_number}@moodlink.com";
        $password = $this->passwords->generateInitialPassword();
        $supabaseUserId = $this->provisioner->createSupabaseAccount($student, $email, $password); // UUID from Supabase auth.users.

        $student->update(['uuid' => $supabaseUserId]);

        // Automatically verify the student to allow mobile app access.
        $this->verifyStudent($student);

        // Email the credentials to the student's personal email.
        $this->mail->sendInitialPasswordToPersonalEmail($student, $email, $password);

        // change their initial password on first login.
        $this->notifications->notifyStudentToChangeInitialPassword($student);

        return [
            'email' => $email,
            'password' => $password,
            'supabase_user_id' => $supabaseUserId,
        ];
    }
}