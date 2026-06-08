<?php

namespace App\Enums;

use App\Traits\EnumValues;

/**
 * A student account's lifecycle state.
 *
 * Verification flow: Unverified -> Pending -> Verified.
 * Verified accounts may later be Suspended (and reactivated back to Verified).
 */
enum StudentStatus: string
{
    use EnumValues;

    case Pending    = 'pending';
    case Verified   = 'verified';
    case Unverified = 'unverified';
    case Suspended  = 'suspended';

    /** Human-readable label for UI badges. */
    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Verified   => 'Verified',
            self::Unverified => 'Unverified',
            self::Suspended  => 'Suspended',
        };
    }

    /** Whether the account is currently usable (verified and not suspended). */
    public function isActive(): bool
    {
        return $this === self::Verified;
    }

    /** Statuses an admin may transition this status into. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending    => [self::Verified, self::Unverified],
            self::Unverified => [self::Verified],
            self::Verified   => [self::Suspended],
            self::Suspended  => [self::Verified],
        };
    }

    /** Guard a requested transition before persisting it. */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
