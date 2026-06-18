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

    case Pending = 'pending';
    case Verified = 'verified';
    case Unverified = 'unverified';
    case Suspended = 'suspended';
}