<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;

/**
 * Keeps the sticky At Risk flag (students.risk_start_date) in sync with the
 * latest mood logs. Mood logs are written outside this app, so there is no
 * create hook to ride on — instead this runs lazily whenever the guidance
 * dashboard reads at-risk data.
 *
 * Two transitions (see SummaryReportService for the rules):
 *   - Entry (Window 1): warning moods reach the threshold  → stamp risk_start_date.
 *   - Recovery (Window 2): recovery moods reach the threshold → clear risk_start_date.
 *
 * The consultation exit is handled separately in AppointmentService.
 */
final class RiskMonitor
{
    public function refresh(): void
    {
        $this->applyRiskWindow();
        $this->applyRecoveryWindow();
    }

    /** Window 1: flag students entering the risk window. */
    private function applyRiskWindow(): void
    {
        $since = PhTime::now()->startOfDay()->subDays(SummaryReportService::RISK_WINDOW_DAYS);

        $entering = StatusDay::studentsEnteringRiskWindow($since);

        if ($entering !== []) {
            Student::whereIn('id', $entering)
                ->update(['risk_start_date' => PhTime::now()->toDateString()]);
        }
    }

    /** Window 2: clear students who recovered in the recovery window. */
    private function applyRecoveryWindow(): void
    {
        $recovered = StatusDay::studentsRecoveredInWindow();

        if ($recovered !== []) {
            Student::whereIn('id', $recovered)
                ->update(['risk_start_date' => null]);
        }
    }
}
