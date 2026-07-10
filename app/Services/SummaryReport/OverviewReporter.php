<?php

declare(strict_types=1);

namespace App\Services\SummaryReport;

use App\Enums\PostMood;
use App\Models\Appointment;
use App\Models\StatusDay;
use App\Models\Student;
use App\Traits\HasFilters;

class OverviewReporter
{
    use HasFilters;

    /**
     * @return array{
     *     total_mood_logs: int,
     *     avg_daily_logs: int,
     *     at_risk_students: int,
     *     appointments_set: int,
     *     distribution: list<array{label: string, count: int, pct: int}>
     * }
     */
    public function build(string $period): array
    {
        $distribution = $this->moodDistribution($period);

        return [
            'total_mood_logs' => array_sum(array_column($distribution, 'count')),
            'avg_daily_logs' => $this->avgDailyLogs($period),
            'at_risk_students' => Student::flaggedAtRiskCount(),
            'appointments_set' => $this->scheduledAppointmentCount($period),
            'distribution' => $distribution,
        ];
    }

    private function avgDailyLogs(string $period): int
    {
        $stats = StatusDay::dailyLogStats($this->summaryReportPeriodStart($period));

        $totalLogs = (int) $stats->total;
        $activeDays = (int) $stats->active_days;

        return $activeDays > 0 ? (int) round($totalLogs / $activeDays) : 0;
    }

    private function scheduledAppointmentCount(string $period): int
    {
        return Appointment::query()
            ->scheduledOrCompleted()
            ->startingFrom($this->summaryReportPeriodStart($period))
            ->count();
    }

    /**
     * @return list<array{label: string, count: int, pct: int}>
     */
    private function moodDistribution(string $period): array
    {
        $counts = StatusDay::moodCountsSince($this->summaryReportPeriodStart($period));
        $grand = $counts->sum();

        return collect(PostMood::availableMoods())
            ->map(function (PostMood $mood) use ($counts, $grand): array {
                $count = (int) $counts->get($mood->value, 0);

                return [
                    'label' => $mood->value,
                    'count' => $count,
                    'pct' => $grand > 0 ? (int) round($count / $grand * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }
}
