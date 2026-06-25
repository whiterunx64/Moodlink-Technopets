<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Unverified = 'unverified';
    case Suspended = 'suspended';
}