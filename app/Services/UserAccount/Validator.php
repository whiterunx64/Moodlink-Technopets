<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Enums\StudentStatus;
use App\Exceptions\StudentAccountException;
use App\Models\Student;

class Validator
{
    /**
     * @throws StudentAccountException when the student is not Pending or Unverified.
     */
    public function ensureStudentCanBeVerified(Student $student): void
    {
        $isEligible = $student->status === StudentStatus::Pending
            || $student->status === StudentStatus::Unverified;

        if (!$isEligible) {
            throw StudentAccountException::studentMustBePendingOrUnverifiedToBeVerified();
        }
    }

    /**
     * @throws StudentAccountException when the student is not Verified.
     */
    public function ensureStudentCanBeSuspended(Student $student): void
    {
        if ($student->status !== StudentStatus::Verified) {
            throw StudentAccountException::studentMustBeVerifiedToBeSuspended();
        }
    }

    /**
     * @throws StudentAccountException when the student is not Suspended.
     */
    public function ensureStudentCanBeReactivated(Student $student): void
    {
        if ($student->status !== StudentStatus::Suspended) {
            throw StudentAccountException::studentMustBeSuspendedToBeReactivated();
        }
    }

    /**
     * @throws StudentAccountException when the student is not Pending.
     */
    public function ensureStudentIsEligibleForRegistration(Student $student): void
    {
        if ($student->status !== StudentStatus::Pending) {
            throw StudentAccountException::studentMustBePendingToRegisterAccount();
        }
    }

    /**
     * @throws StudentAccountException when the student number is not nine digits.
     */
    public function ensureStudentNumberUsesRequiredLength(Student $student): void
    {
        $studentNumber = trim((string) $student->student_number);

        if (preg_match('/^\d{9}$/', $studentNumber) !== 1) {
            throw StudentAccountException::studentIdentifierMustBeNineDigits();
        }
    }

    /**
     * @throws StudentAccountException when the personal email is missing or malformed.
     */
    public function ensureStudentHasValidPersonalEmail(Student $student): void
    {
        $email = trim((string) $student->personal_email);

        if ($email === '') {
            throw StudentAccountException::personalEmailRequiredBeforeAccountCreation();
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw StudentAccountException::personalEmailFormatIsInvalid($email);
        }
    }

    /**
     * @throws StudentAccountException when the student has an active account or is not Pending.
     */
    public function ensureStudentCanBeRejected(Student $student): void
    {
        if ($student->user_id !== null) {
            throw StudentAccountException::cannotRejectStudentWithActiveAccount();
        }

        if ($student->status !== StudentStatus::Pending) {
            throw StudentAccountException::studentMustBePendingToBeRejected();
        }
    }
}
