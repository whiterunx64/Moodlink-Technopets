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
     * @throws DomainException when the student is not Pending or Unverified.
     */
    public function verifyStudent(Student $student): void
    {
        $this->ensureStudentCanBeVerified($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * @throws DomainException when the student is not Pending.
     */
    public function unverifyStudent(Student $student): void
    {
        $this->ensureStudentCanBeUnverified($student);

        $student->update(['status' => StudentStatus::Unverified->value]);
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
     * Account Registration.
     *
     * @throws DomainException when the student is not Pending.
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

        $this->verifyStudent($student);
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

    private function ensureStudentCanBeUnverified(Student $student): void
    {
        if ($student->status !== StudentStatus::Pending) {
            throw new DomainException('Only pending students can be set to unverified.');
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
}
