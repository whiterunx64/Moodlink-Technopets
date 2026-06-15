<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending   = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Rejected  => 'Rejected',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Scheduled => true,
            default => false,
        };
    }
}