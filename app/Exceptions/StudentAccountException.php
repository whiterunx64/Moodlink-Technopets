<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class StudentAccountException extends DomainException
{
    public static function studentMustBePendingOrUnverifiedToBeVerified(): self
    {
        return new self(
            'Only pending or unverified students can be verified.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function studentMustBeVerifiedToBeSuspended(): self
    {
        return new self(
            'Only verified students can be suspended.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function studentMustBeSuspendedToBeReactivated(): self
    {
        return new self(
            'Only suspended students can be reactivated.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function studentMustBePendingToRegisterAccount(): self
    {
        return new self(
            'Only pending students can be registered.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function studentIdentifierMustBeNineDigits(): self
    {
        return new self(
            'This student does not have the required 9-digit student ID. '
            . "Please check and verify this student's ID, then ask them to re-create their registration "
            . 'with a correct student ID, or reject this registration.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function studentMustBePendingToBeRejected(): self
    {
        return new self(
            'Only pending students can be rejected. Suspend active accounts instead.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function cannotRejectStudentWithActiveAccount(): self
    {
        return new self(
            'This student has an active account and cannot be rejected. Suspend it instead.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    // ── Personal email validation ─────────────────────────────────────────────

    public static function personalEmailRequiredBeforeAccountCreation(): self
    {
        return new self(
            'Account creation failed because the student does not have a personal email address on file. '
            . 'Please add a valid email address before creating the account so the login credentials can be delivered.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function personalEmailFormatIsInvalid(string $email): self
    {
        return new self(
            "The student's personal email address (\"{$email}\") is invalid. "
            . 'Please correct the email address and create the account again.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    // ── Upstream account provider (Supabase) ──────────────────────────────────

    public static function studentAccountAlreadyExists(?Throwable $previous = null): self
    {
        return new self(
            'An account with this student ID already exists. Please use the forgot password option to recover your account, or reject this registration if it was submitted by mistake.',
            Response::HTTP_CONFLICT,
            $previous,
        );
    }

    public static function studentAccountCreationFailed(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be created because Supabase rejected the request. '
            . 'Please verify the student details and try again, or contact your system administrator.',
            Response::HTTP_BAD_GATEWAY,
            $previous,
        );
    }

    public static function malformedProviderResponse(): self
    {
        return new self(
            'The student account may have been created, but Supabase did not return a usable account id. '
            . 'Please contact your system administrator.',
            Response::HTTP_BAD_GATEWAY,
        );
    }


    public static function studentAccountDeletionFailed(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be deleted because the Supabase authentication account could not be removed. '
            . 'No student data has been deleted. Please try again. If the problem continues, contact your system administrator.',
            Response::HTTP_BAD_GATEWAY,
            $previous,
        );
    }

    public static function accountServiceUnavailable(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be created because the Supabase authentication service is temporarily unavailable. '
            . 'Please try again in a few minutes. If the problem continues, contact your system administrator.',
            Response::HTTP_SERVICE_UNAVAILABLE,
            $previous,
        );
    }

    public static function deletionFailedDueToServerError(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be deleted because the system encountered an error while processing the request. '
            . 'Please contact your system administrator to verify the application, database, and Supabase services before trying again.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous,
        );
    }

    public static function registrationFailedDueToServerError(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be created because the system encountered an error while processing the registration. '
            . 'The account may have been partially created. Please contact your system administrator to verify the application, database, and Supabase services before trying again.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous,
        );
    }

    public static function registrationFailedDueToUnexpectedError(?Throwable $previous = null): self
    {
        return new self(
            'The student account could not be created because an unexpected error occurred during registration. '
            . 'Please contact your system administrator to verify the application, database, and Supabase services before trying again to avoid creating duplicate accounts.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous,
        );
    }

    public static function statusChangeFailedDueToServerError(string $action, ?Throwable $previous = null): self
    {
        return new self(
            "The student account could not be {$action} because the system encountered an error while processing the request. "
            . 'Please contact your system administrator to verify the application and database before trying again.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous,
        );
    }
}