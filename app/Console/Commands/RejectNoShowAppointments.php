<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AppointmentManager;
use Illuminate\Console\Command;

class RejectNoShowAppointments extends Command
{
    protected $signature = 'appointments:reject-no-shows';

    protected $description = 'Mark scheduled sessions as missed when the student has not checked in within the grace period.';

    public function handle(AppointmentManager $service): int
    {
        $rejected = $service->rejectNoShowSessions();

        $this->info("No-show sessions marked as missed: {$rejected}");

        return self::SUCCESS;
    }
}
