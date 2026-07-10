<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostMood;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use Illuminate\Support\Collection;

use function count;
use function in_array;

final class RiskMonitor
{
    /** Window A size that flags a student At Risk. */
    public const WINDOW_A_THRESHOLD = 5;

    /** Window B size that fires the recovery helper. */
    public const WINDOW_B_TRIGGER = 3;

    /** How many points the helper pops off Window A each time Window B fires. */
    public const WINDOW_A_DECREMENT = 2;

    // ─────────────────────────────────────────────────────────────
    // At-risk window algorithm
    // ─────────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, StatusDay>  $moodCheckIns
     * @return array{window_a: int, window_b: int}
     */
    public function calculateWindows(Collection $moodCheckIns): array
    {
        $windowA = 0;
        $windowB = 0;

        foreach ($moodCheckIns as $checkIn) {
            if ($this->isWarningMood($checkIn->mood)) {
                $windowA++;

                continue;
            }

            if (! $this->isRecoveryMood($checkIn->mood)) {
                continue;
            }

            $windowB++;

            if ($windowB >= self::WINDOW_B_TRIGGER) {
                $windowA = max(0, $windowA - self::WINDOW_A_DECREMENT);
                $windowB = 0;
            }
        }

        return ['window_a' => $windowA, 'window_b' => $windowB];
    }
    public function isAtRisk(int $windowACount): bool
    {
        return $windowACount >= self::WINDOW_A_THRESHOLD;
    }

    public function isWarningMood(PostMood $mood): bool
    {
        return in_array($mood, [PostMood::Stressed, PostMood::Drained], true);
    }

    public function isRecoveryMood(PostMood $mood): bool
    {
        return in_array($mood, [PostMood::Content, PostMood::Excited], true);
    }

    /**
     * @param  Collection<int, StatusDay>  $moodCheckIns
     */
    public function isCheckInStreamAtRisk(Collection $moodCheckIns): bool
    {
        return $this->isAtRisk($this->calculateWindows($moodCheckIns)['window_a']);
    }

    /**
     * @param  Collection<int, StatusDay>  $checkIns
     * @return Collection<string, int>
     */
    public function warningMoodCountsByFrequency(Collection $checkIns): Collection
    {
        return $checkIns
            ->filter(fn(StatusDay $checkIn): bool => $this->isWarningMood($checkIn->mood))
            ->groupBy(fn(StatusDay $checkIn): string => $checkIn->mood->value)
            ->map(fn(Collection $group): int => $group->count())
            ->sortDesc();
    }

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

            $windowACount = $this->calculateWindows($moodCheckIns)['window_a'];
            $isAtRisk = $this->isAtRisk($windowACount);
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