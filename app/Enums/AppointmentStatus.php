<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'Pending';
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Rejected = 'Rejected';

    /**
     * UI tab keys this status counts toward. A status may appear under
     * more than one tab (e.g. Rejected shows under both History and Rejected).
     *
     * @return array<int, string>
     */
    public function tabKeys(): array
    {
        return match ($this) {
            self::Pending => ['requests'],
            self::Scheduled => ['scheduled'],
            self::Completed => ['history'],
            self::Rejected => ['history', 'rejected'],
        };
    }

    /**
     * Build per-tab totals from raw counts keyed by status value.
     * Every tab is present, defaulting to 0.
     *
     * @param  array<string, int>  $countsByStatus  Raw counts keyed by status value.
     * @return array<string, int>
     */
    public static function tabCounts(array $countsByStatus): array
    {
        $tabCounts = ['requests' => 0, 'scheduled' => 0, 'history' => 0, 'rejected' => 0];

        foreach (self::cases() as $status) {
            $count = $countsByStatus[$status->value] ?? 0;

            foreach ($status->tabKeys() as $tab) {
                $tabCounts[$tab] += $count;
            }
        }

        return $tabCounts;
    }
}