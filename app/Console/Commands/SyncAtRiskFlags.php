<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RiskMonitor;
use Illuminate\Console\Command;

class SyncAtRiskFlags extends Command
{
    protected $signature = 'students:sync-at-risk';

    protected $description = 'Sync the stored At Risk marker (students.risk_start_date) with each student\'s live Window A score.';

    public function handle(RiskMonitor $monitor): int
    {
        $changed = $monitor->updateStudentAtRiskStatusFromWindowAScore();

        $this->info("At-risk markers updated: {$changed}");

        return self::SUCCESS;
    }
}
