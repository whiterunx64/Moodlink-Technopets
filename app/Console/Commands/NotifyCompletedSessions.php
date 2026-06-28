<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AppointmentManager;
use Illuminate\Console\Command;

class NotifyCompletedSessions extends Command
{
    protected $signature = 'appointments:notify-completed';

    protected $description = 'Notify students that their session is complete once its scheduled end time has passed.';

    public function handle(AppointmentManager $service): int
    {
        $notified = $service->notifyCompletedSessions();

        $this->info("Session-completed notifications sent: {$notified}");

        return self::SUCCESS;
    }
}
