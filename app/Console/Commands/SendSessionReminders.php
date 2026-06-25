<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AppointmentService;
use Illuminate\Console\Command;

class SendSessionReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Email students whose scheduled session begins in about an hour.';

    public function handle(AppointmentService $service): int
    {
        $sent = $service->sendUpcomingSessionReminders();

        $this->info("Session reminder emails sent: {$sent}");

        return self::SUCCESS;
    }
}
