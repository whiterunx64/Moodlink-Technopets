<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AppointmentManager;
use Illuminate\Console\Command;

class NotifyAwaitingCheckIns extends Command
{
    protected $signature = 'appointments:notify-awaiting';

    protected $description = 'Raise an admin inbox alert for scheduled sessions that have started but are not yet checked in.';

    public function handle(AppointmentManager $service): int
    {
        $created = $service->flagSessionsAwaitingCheckIn();

        $this->info("Awaiting-check-in alerts created: {$created}");

        return self::SUCCESS;
    }
}
