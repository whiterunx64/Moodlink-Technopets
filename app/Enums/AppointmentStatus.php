<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'Pending';
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Rejected = 'Rejected';
    case Missed = 'Missed';

    /**
     * @return array<int, string>
     */
    public function tabKeys(): array
    {
        return match ($this) {
            self::Pending => ['requests'],
            self::Scheduled => ['scheduled'],
            self::Completed => ['history'],
            self::Rejected => ['history', 'rejected'],
            self::Missed => ['missed'],
        };
    }

    public static function fromTab(string $tab): self
    {
        return match ($tab) {
            'scheduled' => self::Scheduled,
            'missed' => self::Missed,
            'history' => self::Completed,
            'rejected' => self::Rejected,
            default => self::Pending,
        };
    }
}