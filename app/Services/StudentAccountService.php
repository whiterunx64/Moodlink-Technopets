<?php

namespace App\Services;

use App\Contracts\SupabaseAuthInterface;
use App\Enums\StudentStatus;
use App\Models\Student;
use DomainException;

final class StudentAccountService
{
    public function __construct(
        private readonly SupabaseAuthInterface $supabase,
    ) {
    }

    /**
     * Transition a student to a new status, enforcing allowed transitions.
     *
     * @throws DomainException when the transition is not allowed.
     */
    public function changeStudentStatus(Student $student, StudentStatus $targetStatus): Student
    {
        $this->ensureStatusChangeIsAllowed($student, $targetStatus);

        $student->update(['status' => $targetStatus]);

        return $student;
    }


    /**
     * Create Supabase account and mark student as verified.
     * 
     * @throws DomainException when the student is not pending.
     * @throws \Exception when Supabase signup fails.
     */
    public function createSupabaseAccountForStudent(Student $student, string $email, string $password): void
    {
        $this->ensureStudentIsEligibleForRegistration($student);

        $supabaseMetadata = [
            'student_id' => $student->id,
            'name' => $student->name,
        ];

        $this->supabase->signUp(
            email: $email,
            password: $password,
            data: $supabaseMetadata,
        );

        $this->changeStudentStatus($student, StudentStatus::Verified);
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function ensureStatusChangeIsAllowed(Student $student, StudentStatus $targetStatus): void
    {
        if (!$student->status->canTransitionTo($targetStatus)) {
            throw new DomainException(
                "Cannot change student status from {$student->status->value} to {$targetStatus->value}."
            );
        }
    }

    private function ensureStudentIsEligibleForRegistration(Student $student): void
    {
        if ($student->status !== StudentStatus::Pending) {
            throw new DomainException('Only pending students can be registered.');
        }
    }
}
