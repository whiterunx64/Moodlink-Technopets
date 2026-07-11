<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Missed = 'Missed';

    /**
     * @return array<int, string>
     */
    public function tabKeys(): array
    {
        return match ($this) {
            self::Scheduled => ['scheduled'],
            self::Completed => ['history'],
            self::Missed => ['missed'],
        };
    }

    public static function fromTab(string $tab): self
    {
        return match ($tab) {
            'missed' => self::Missed,
            'history' => self::Completed,
            default => self::Scheduled,
        };
    }
}