<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostMood;
use App\Models\Appointment;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use App\Traits\HasFilters;
use Illuminate\Support\Collection;

use function in_array;
class SummaryReportService
{
    use HasFilters;
    /** Window A size that flags a student At Risk. */
    public const WINDOW_A_THRESHOLD = 2;

    /** Window B size that fires the helper. */
    public const WINDOW_B_TRIGGER = 3;

    /** How many moods the helper pops off Window A each time Window B fires. */
    public const WINDOW_A_DECREMENT = 2;


    // ─────────────────────────────────────────────────────────────
    // Public Methods
    // ─────────────────────────────────────────────────────────────

    public function overview(string $period): array
    {
        $moodDistribution = $this->moodDistribution($period); // Retrieve mood breakdown.

        return [
            'total_mood_logs' => array_sum(array_column($moodDistribution, 'count')), // Mood logs in the period.
            'avg_daily_logs' => $this->avgDailyLogs($period),
            'at_risk_students' => Student::flaggedAtRiskCount(),
            'appointments_set' => Appointment::getScheduledCount($period),
            'distribution' => $moodDistribution,
        ];
    }

    public function atRiskStudents(): array
    {
        $students = Student::flaggedAtRiskList(); // students the marker says are At Risk
        $checkInsByStudentId = StatusDay::moodCheckInsForStudents($students->pluck('id')->all());

        return $students
            ->map(function (Student $student) use ($checkInsByStudentId): array {
                $checkIns = $checkInsByStudentId->get($student->id) ?? new Collection();
                $windowCounts = self::calculateWindowCounts($checkIns);
                $warningMoodCounts = $this->warningMoodCountsByFrequency($checkIns);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_number' => $student->student_number,
                    'program' => $student->program,
                    'moods' => $warningMoodCounts->keys()->all(),
                    'last_log' => $checkIns->last()?->date?->diffForHumans(),
                    'window_a_count' => $windowCounts['window_a'],
                    'warning_mood_counts' => $warningMoodCounts->all(),
                    'time_at_risk' => $student->risk_start_date
                            ?->diff(PhTime::now())
                        ->forHumans(parts: 1),
                    'has_consultation' => false,
                ];
            })
            ->sortByDesc('window_a_count')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, StatusDay>  $moodCheckIns  Mood check-ins ordered from oldest to newest
     * @return array{window_a: int, window_b: int}
     */
    public static function calculateWindowCounts(Collection $moodCheckIns): array
    {
        $windowACount = 0;
        $windowBCount = 0;

        foreach ($moodCheckIns as $checkIn) {
            if (self::isWarningMood($checkIn->mood)) {
                $windowACount = self::addWarningCheckInToWindowA($windowACount);
                continue;
            }

            if (!self::isRecoveryMood($checkIn->mood)) {
                continue;
            }
            [
                'window_a' => $windowACount,
                'window_b' => $windowBCount,
            ] = self::addRecoveryCheckInToWindowB($windowACount, $windowBCount);
        }

        return [
            'window_a' => $windowACount,
            'window_b' => $windowBCount
        ];
    }

    public static function isWindowAAtRisk(int $windowACount): bool
    {
        return $windowACount >= self::WINDOW_A_THRESHOLD;
    }

    // ─────────────────────────────────────────────────────────────
    // Private Methods
    // ─────────────────────────────────────────────────────────────

    private static function addWarningCheckInToWindowA(int $windowAScore): int
    {
        return $windowAScore + 1;
    }

    private static function addRecoveryCheckInToWindowB(int $windowAScore, int $windowBScore): array
    {
        $windowBScore++;

        if ($windowBScore >= self::WINDOW_B_TRIGGER) {
            // Fire to reduce Window A risk count.
            $windowAScore = max(0, $windowAScore - self::WINDOW_A_DECREMENT);
            $windowBScore = 0;
        }
        return [
            'window_a' => $windowAScore,
            'window_b' => $windowBScore
        ];
    }

    private static function isWarningMood(PostMood $mood): bool
    {
        return in_array($mood, [
            PostMood::Stressed,
            PostMood::Drained
        ], true);
    }

    private static function isRecoveryMood(PostMood $mood): bool
    {
        return in_array($mood, [
            PostMood::Content,
            PostMood::Excited
        ], true);
    }

    private function avgDailyLogs(string $period): int
    {
        $stats = StatusDay::dailyLogStats(
            $this->summaryReportPeriodStart($period)
        );

        $totalLogs = (int) $stats->total;
        $activeDays = (int) $stats->active_days;

        return $activeDays > 0
            ? (int) round($totalLogs / $activeDays)
            : 0;
    }


    /**
     * @param  Collection<int, StatusDay>  $checkIns
     * @return Collection<string, int>  warning mood value => count, most frequent first
     */
    private function warningMoodCountsByFrequency(Collection $checkIns): Collection
    {
        return $checkIns
            ->filter(fn(StatusDay $checkIn): bool => self::isWarningMood($checkIn->mood))
            ->groupBy(fn(StatusDay $checkIn): string => $checkIn->mood->value)
            ->map(fn(Collection $group): int => $group->count())
            ->sortDesc();
    }

    /**
     * @return array<int, array{label: string, count: int, pct: int}>
     */
    private function moodDistribution(string $period): array
    {
        $from = $this->summaryReportPeriodStart($period);
        $counts = StatusDay::moodCountsSince($from);
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