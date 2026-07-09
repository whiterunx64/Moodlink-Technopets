<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostMood;
use App\Models\Appointment;
use App\Models\Post;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use App\Traits\HasFilters;
use Illuminate\Support\Collection;

use function array_slice;
use function count;
use function in_array;
class SummaryReportService
{
    use HasFilters;
    /** Window A size that flags a student At Risk. */
    public const WINDOW_A_THRESHOLD = 5;

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
            'appointments_set' => $this->scheduledAppointmentCount($period),
            'distribution' => $moodDistribution,
        ];
    }

    /**
     * @return list<array{program: string, total: int, excited: int, content: int, stressed: int, drained: int, at_risk: int}>
     */
    public function perProgramMoodCounts(string $period): array
    {
        $periodStart = $this->summaryReportPeriodStart($period);
        $programMoodRows = StatusDay::StudentMoodSummaryByProgram($periodStart);
        $atRiskCountsByProgram = Student::atRiskCountsGroupedByProgram();

        return $programMoodRows
            ->map(fn(StatusDay $programRow): array => [
                'program' => $programRow->program,
                'total' => $programRow->total_mood_entries,
                'excited' => $programRow->excited_count,
                'content' => $programRow->content_count,
                'stressed' => $programRow->stressed_count,
                'drained' => $programRow->drained_count,
                'at_risk' => $atRiskCountsByProgram->get($programRow->program, 0),
            ])
            ->values()
            ->all();
    }

    public function programOverview(string $program, string $period, ?string $search = null): array
    {
        $moodCounts = StatusDay::moodCountsSince(
            $this->summaryReportPeriodStart($period),
            $program,
        );

        $students = Student::verifiedListByProgram($program, $search);
        $checkInsByStudentId = StatusDay::moodCheckInsForStudents($students->pluck('id')->all());

        return [
            'program' => $program,
            'total' => $moodCounts->sum(),
            'excited' => $moodCounts->get(PostMood::Excited->value, 0),
            'content' => $moodCounts->get(PostMood::Content->value, 0),
            'stressed' => $moodCounts->get(PostMood::Stressed->value, 0),
            'drained' => $moodCounts->get(PostMood::Drained->value, 0),
            'at_risk' => Student::atRiskCountForProgram($program),
            'students' => $students
                ->map(fn(Student $student): array => $this->programStudentRow($student, $checkInsByStudentId))
                ->values()
                ->all(),
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

    public function studentMoodReport(Student $student, int $trendDays): array
    {
        $moodEntryCounts = StatusDay::moodEntryCountsForStudent($student->id);
        $postCounts = Post::totalAndFlaggedCountsForStudent($student->id);
        $trendData = $this->dailyMoodTrendForStudent($student->id, $trendDays);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'full_name' => $student->anonymous_name ?? $student->name,
            'student_number' => $student->student_number,
            'year_level' => $student->year_level_label,
            'program' => $student->program,
            'initials' => $student->studentNameInitials,
            'mood_summary' => [
                'excited' => $moodEntryCounts->get(PostMood::Excited->value, 0),
                'content' => $moodEntryCounts->get(PostMood::Content->value, 0),
                'stressed' => $moodEntryCounts->get(PostMood::Stressed->value, 0),
                'drained' => $moodEntryCounts->get(PostMood::Drained->value, 0),
            ],
            'summary_stats' => [
                'total_mood_entries' => $moodEntryCounts->sum(),
                'total_posts' => $postCounts->total,
                'flagged_posts' => $postCounts->flagged,
            ],
            'trend' => $this->moodTrendDirection($trendData),
            'trend_data' => $trendData,
            'recent_entries' => StatusDay::recentMoodEntriesForStudent($student->id)
                ->map(fn(StatusDay $entry): array => [
                    'id' => $entry->id,
                    'mood' => $entry->mood->value,
                    'content' => $entry->journal,
                    'date' => $entry->date->format('M j, Y'),
                ])
                ->all(),
        ];
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

    /**
     * @param  Collection<int, Collection<int, StatusDay>>  $checkInsByStudentId
     * @return array{id: int, name: string, initials: string, student_number: string, year_level: string, trend: string}
     */
    private function programStudentRow(Student $student, Collection $checkInsByStudentId): array
    {
        $checkIns = $checkInsByStudentId->get($student->id) ?? new Collection();
        $windowCounts = self::calculateWindowCounts($checkIns);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'initials' => $student->studentNameInitials,
            'student_number' => $student->student_number,
            'year_level' => $student->year_level_label,
            'trend' => self::isWindowAAtRisk($windowCounts['window_a']) ? 'Declining' : 'Stable',
        ];
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
     * @return list<array{
     *     date: string,
     *     label: string,
     *     score: float|null,
     *     dominant_mood: string|null,
     *     posts: list<array{id: int, mood: string, content: string|null, time: string}>
     * }>
     */
    private function dailyMoodTrendForStudent(int $studentId, int $trendDays): array
    {
        $trendWindowStart = PhTime::now()->startOfDay()->subDays($trendDays - 1);

        $postsByDate = Post::moodPostsForStudentSince($studentId, $trendWindowStart->utc())
            ->groupBy(fn(Post $post): string => PhTime::fromUtc($post->datetime)->toDateString());

        return collect(range(0, $trendDays - 1))
            ->map(function (int $dayOffset) use ($trendWindowStart, $postsByDate, $trendDays): array {
                $day = $trendWindowStart->copy()->addDays($dayOffset);
                /** @var Collection<int, Post> $posts */
                $posts = $postsByDate->get($day->toDateString()) ?? new Collection();

                return [
                    'date' => $day->toDateString(),
                    'label' => $trendDays === 7 ? $day->format('D') : $day->format('M j'),
                    'score' => $posts->isEmpty()
                        ? null
                        : round($posts->avg(fn(Post $post): float => $post->mood->wellbeingScore()), 1),
                    'dominant_mood' => $this->dominantMoodForPosts($posts),
                    'posts' => $posts
                        ->map(fn(Post $post): array => [
                            'id' => $post->id,
                            'mood' => $post->mood->value,
                            'content' => $post->content,
                            'time' => PhTime::fromUtc($post->datetime)->format('g:i A'),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Post>  $posts
     */
    private function dominantMoodForPosts(Collection $posts): ?string
    {
        if ($posts->isEmpty()) {
            return null;
        }

        return $posts
            ->groupBy(fn(Post $post): string => $post->mood->value)
            ->sortByDesc(fn(Collection $group): array => [
                $group->count(),
                $group->first()->mood->wellbeingScore(),
            ])
            ->keys()
            ->first();
    }

    /**
     * @param  list<array{label: string, score: float|null}>  $trendData
     */
    private function moodTrendDirection(array $trendData): string
    {
        $scores = array_values(array_filter(
            array_column($trendData, 'score'),
            fn(?float $score): bool => $score !== null,
        ));

        if (count($scores) < 2) {
            return 'Stable';
        }

        $midpoint = intdiv(count($scores), 2);
        $earlierHalf = array_slice($scores, 0, $midpoint);
        $laterHalf = array_slice($scores, $midpoint);

        $delta = (array_sum($laterHalf) / count($laterHalf))
            - (array_sum($earlierHalf) / count($earlierHalf));

        return match (true) {
            $delta > 0.25 => 'Improving',
            $delta < -0.25 => 'Declining',
            default => 'Stable',
        };
    }

    private function scheduledAppointmentCount(string $period): int
    {
        $from = $this->summaryReportPeriodStart($period);

        return Appointment::query()
            ->scheduledOrCompleted()
            ->startingFrom($from)
            ->count();
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