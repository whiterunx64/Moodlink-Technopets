<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

final class AppointmentException extends DomainException
{
    public static function appointmentMustBePendingToApprove(): self
    {
        return new self(
            'Only pending appointments can be approved.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function appointmentMustBePendingToReject(): self
    {
        return new self(
            'Only pending appointments can be rejected.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function appointmentMustBeScheduledToComplete(): self
    {
        return new self(
            'Only scheduled appointments can be marked as completed.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    // ── Booking conflicts ─────────────────────────────────────────────────────

    public static function studentAlreadyHasBookedSlot(): self
    {
        return new self(
            'This student already has a booked slot. Complete or release it before booking another.',
            Response::HTTP_CONFLICT,
        );
    }

    public static function studentAlreadyHasOpenConsultation(): self
    {
        return new self(
            'This student already has an open consultation.',
            Response::HTTP_CONFLICT,
        );
    }

    public static function noAvailableSlotForAppointmentTime(): self
    {
        return new self(
            'No available slot matches this appointment time.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function slotAlreadyBookedByAnotherStudent(): self
    {
        return new self(
            'That time slot has already been booked by another student.',
            Response::HTTP_CONFLICT,
        );
    }

    // ── Schedule slot management ──────────────────────────────────────────────

    public static function slotAlreadyExistsAtTime(): self
    {
        return new self(
            'A slot already exists at this time.',
            Response::HTTP_CONFLICT,
        );
    }

    public static function bookedSlotCannotBeDeleted(): self
    {
        return new self(
            'Cannot delete a slot that is already booked.',
            Response::HTTP_CONFLICT,
        );
    }
}