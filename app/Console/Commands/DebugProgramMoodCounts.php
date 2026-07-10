<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SummaryReportsManager;
use Illuminate\Console\Command;

class DebugProgramMoodCounts extends Command
{
    protected $signature = 'reports:program-mood-counts {--period=this_week : Reporting window — this_week, this_month, or all_time}';

    protected $description = 'Print the per-program mood breakdown (total, per-mood counts, at-risk) that powers the Summary Reports "Programs" tab.';

    public function handle(SummaryReportsManager $report): int
    {
        $period = (string) $this->option('period');
        $programMoodCounts = $report->perProgramMoodCounts($period);

        $this->table(
            ['Program', 'Total', 'Excited', 'Content', 'Stressed', 'Drained', 'At Risk'],
            array_map(fn(array $row): array => [
                $row['program'],
                $row['total'],
                $row['excited'],
                $row['content'],
                $row['stressed'],
                $row['drained'],
                $row['at_risk'],
            ], $programMoodCounts),
        );

        $this->info(sprintf(
            'Period: %s  |  programs shown: %d  |  total mood logs: %d',
            $period,
            count($programMoodCounts),
            array_sum(array_column($programMoodCounts, 'total')),
        ));

        return self::SUCCESS;
    }
}
