<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use Illuminate\Support\Collection;

use function count;
final class RiskMonitor
{

    // ─────────────────────────────────────────────────────────────
    // Public Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * @return int  how many markers changed
     */
    public function updateStudentAtRiskStatusFromWindowAScore(): int
    {
        $verifiedStudents = Student::getVerifiedStudents();
        $allStudentMoodCheckIns = StatusDay::moodCheckInsForStudents(
            $verifiedStudents->pluck('id')->all()
        );

        // Store students becoming or exiting At Risk based on their Window A score.
        $studentsEnteringAtRisk = [];
        $studentsExitingFromRisk = [];

        foreach ($verifiedStudents as $student) {
            $moodCheckIns = $allStudentMoodCheckIns->get($student->id) ?? new Collection();
            $windowACount = SummaryReportService::calculateWindowCounts($moodCheckIns)['window_a'];

            $isAtRisk = SummaryReportService::isWindowAAtRisk($windowACount);
            $isCurrentlyAtRisk = $student->risk_start_date !== null;

            // Check if Window A reached the risk threshold and the student is not marked At Risk yet.
            if ($isAtRisk && !$isCurrentlyAtRisk) {
                $studentsEnteringAtRisk[] = $student->id;
            }

            // Check if Window A dropped below the risk threshold and the student is still marked At Risk.
            if (!$isAtRisk && $isCurrentlyAtRisk) {
                $studentsExitingFromRisk[] = $student->id;
            }
        }

        $this->markStudentsAsAtRisk($studentsEnteringAtRisk);
        $this->clearRecoveredStudents($studentsExitingFromRisk);

        return count($studentsEnteringAtRisk) + count($studentsExitingFromRisk);
    }

    // ─────────────────────────────────────────────────────────────
    // Private Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<int> $studentIds
     */
    private function markStudentsAsAtRisk(array $studentIds): void
    {
        if ($studentIds === []) {
            return;
        }
        Student::whereIn('id', $studentIds)
            ->update([
                'risk_start_date' => PhTime::now()->toDateString(),
            ]);
    }

    /**
     * @param array<int> $studentIds
     */
    private function clearRecoveredStudents(array $studentIds): void
    {
        if ($studentIds === []) {
            return;
        }
        Student::whereIn('id', $studentIds)
            ->update([
                'risk_start_date' => null,
            ]);
    }
}