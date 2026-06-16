<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending   = 'Pending';
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Rejected  = 'Rejected';

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