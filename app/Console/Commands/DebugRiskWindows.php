<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StatusDay;
use App\Models\Student;
use App\Services\RiskMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DebugRiskWindows extends Command
{
    protected $signature = 'students:risk-windows {--at-risk : Only show students currently at risk}';

    protected $description = 'Print each verified student\'s Window A / Window B bucket sizes for debugging the at-risk scoring.';

    public function handle(RiskMonitor $monitor): int
    {
        $students = Student::getVerifiedStudents();
        $logs = StatusDay::moodCheckInsForStudents($students->pluck('id')->all());

        $rows = $students
            ->map(function (Student $student) use ($logs, $monitor): array {
                $log = $logs->get($student->id) ?? new Collection();
                $state = $monitor->calculateWindows($log);
                $atRisk = $monitor->isAtRisk($state['window_a']);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'window_a' => $state['window_a'],
                    'window_b' => $state['window_b'],
                    'at_risk' => $atRisk ? 'YES' : '',
                    'marker' => $student->risk_start_date?->toDateString() ?? '—',
                    '_atRisk' => $atRisk,
                ];
            })
            ->when($this->option('at-risk'), fn(Collection $r) => $r->filter(fn(array $row): bool => $row['_atRisk']))
            ->sortByDesc('window_a')
            ->values();

        $this->table(
            ['ID', 'Name', 'Window A', 'Window B', 'At Risk', 'Marker'],
            $rows->map(fn(array $row): array => [
                $row['id'],
                $row['name'],
                $row['window_a'],
                $row['window_b'],
                $row['at_risk'],
                $row['marker'],
            ])->all(),
        );

        $this->info(sprintf(
            'At Risk when Window A >= %d  |  Window B fires at %d (then -%d to Window A)  |  shown: %d',
            RiskMonitor::WINDOW_A_THRESHOLD,
            RiskMonitor::WINDOW_B_TRIGGER,
            RiskMonitor::WINDOW_A_DECREMENT,
            $rows->count(),
        ));

        return self::SUCCESS;
    }
}