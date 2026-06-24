<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostMood;
use App\Models\Appointment;
use App\Models\StatusDay;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Builds the data payloads for the Summary Reports screens (overview, sections,
 * at-risk, per-section detail, per-student detail) and owns the at-risk rules.
 *
 * Weighted Risk Scoring Algorithm (WRSA) — two-window, sticky model. Two
 * independent windows decide when a student enters and leaves the At Risk list:
 *
 *   Window 1 — Entry. Count warning moods (Stressed, Drained) in the most recent
 *              RISK_WINDOW_DAYS. Reaching RISK_THRESHOLD flags the student and
 *              stamps students.risk_start_date = today.
 *
 *   Window 2 — Recovery (override). While flagged, count recovery moods
 *              (Content, Excited) logged since risk_start_date. Reaching
 *              RECOVERY_THRESHOLD clears the flag — the student improved on their
 *              own and drops off the list.
 *
 * The flag is STICKY: once set it stays until one of two exits fires —
 *   (a) recovery (Window 2), or
 *   (b) a counselor consultation (handled in AppointmentService).
 * Time alone never clears it; instead daysAtRisk drives the escalation label.
 *
 * Tune the numbers here; nothing else in the app hard-codes them. At-risk reads
 * first sync the sticky flags via RiskMonitor so the report reflects the latest
 * mood logs.
 */
class SummaryReportService
{
    /** Moods that raise risk (Window 1). */
    public const WARNING_MOODS = [PostMood::Stressed, PostMood::Drained];

    /** Moods that signal recovery (Window 2). */
    public const RECOVERY_MOODS = [PostMood::Content, PostMood::Excited];

    /** Window 1: how many days back to look for warning moods. */
    public const RISK_WINDOW_DAYS = 7;

    /** Window 1: warning moods within the window needed to ENTER At Risk. */
    public const RISK_THRESHOLD = 1;

    /** Window 2: recovery moods since risk_start_date needed to EXIT (override). */
    public const RECOVERY_THRESHOLD = 6;

    /** Days at risk after which a consultation is recommended to the counselor. */
    public const RECOMMEND_AFTER_DAYS = 14;

    /** Days at risk after which a consultation is strongly suggested. */
    public const CONSULT_AFTER_DAYS = 30;

    public function __construct(
        private readonly RiskMonitor $riskMonitor,
    ) {
    }

    public function overview(string $period): array
    {
        $this->riskMonitor->refresh(); // Overview shows the at-risk count.

        $moodDistribution = $this->moodDistribution($period); // Retrieve mood breakdown.

        return [
            'total_mood_logs' => array_sum(array_column($moodDistribution, 'count')), // Mood logs in the period.
            'avg_daily_logs' => StatusDay::avgDailyLogs($period),
            'at_risk_students' => Student::getAtRiskCount(),
            'appointments_set' => Appointment::getScheduledCount($period),
            'distribution' => $moodDistribution,
        ];
    }

    public function atRiskStudents(): array
    {
        $this->riskMonitor->refresh(); // Sync sticky flags from latest mood log
        
        $students = Student::atRiskList();
        $studentIds = $students->pluck('id')->all(); // retrieve student ids
        
        $summaries = $this->atRiskSummaries($studentIds);

        return $students 
            ->map(function (Student $student) use ($summaries): array {
                $summary = $summaries[$student->id] ?? ['moods' => [], 'lastLog' => null];
                $daysAtRisk = $student->days_at_risk;
                
                return [
                  'id' => $student->id,
                  'name' => $student->name,
                  'student_number' => $student->student_number,
                  'section' => $student->section,
                  'moods' => $summary['moods'],
                  'days_at_risk' => $daysAtRisk,
                  'last_log' => $summary['lastLog'],
                  'risk_start_day' => $student->risk_start_date?->toDateString(),
                  'level' => self::escalation($daysAtRisk),
                  'has_consultation' => false,
                ];
            })
            ->values()
            ->all();
    }

    // ── Weighted Risk Scoring rules (WRSA) ─────────────────────────────────────

    /**
     * Warning mood values, for use in database queries.
     *
     * @return list<string>
     */
    public static function warningMoodValues(): array
    {
        return array_map(static fn(PostMood $mood): string => $mood->value, self::WARNING_MOODS);
    }

    /**
     * Recovery mood values, for use in database queries.
     *
     * @return list<string>
     */
    public static function recoveryMoodValues(): array
    {
        return array_map(static fn(PostMood $mood): string => $mood->value, self::RECOVERY_MOODS);
    }

    /** Whether a mood counts as a warning sign (Stressed/Drained). */
    public static function isWarning(PostMood $mood): bool
    {
        return \in_array($mood, self::WARNING_MOODS, true);
    }

    /**
     * Classify how far an at-risk student has escalated, based on how many days
     * they have remained at risk. Drives the suggest-only consultation prompt.
     *
     * @return 'monitor'|'recommend'|'consult'
     */
    public static function escalation(int $daysAtRisk): string
    {
        if ($daysAtRisk >= self::CONSULT_AFTER_DAYS) {
            return 'consult';
        }

        if ($daysAtRisk >= self::RECOMMEND_AFTER_DAYS) {
            return 'recommend';
        }

        return 'monitor';
    }

    // ── Private data builders ──────────────────────────────────────────────────

    /**
     * Shape mood counts into label/count/pct rows for every mood case.
     *
     * @return array<int, array{label: string, count: int, pct: int}>
     */
    private function moodDistribution(string $period): array
    {
        $counts = StatusDay::getMoodCounts($period);
        $grand = $counts->sum();

        return collect(PostMood::cases())
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

    /**
     * Build per-student at-risk mood summaries keyed by student id. Looks only at
     * entries logged since the student became at risk (StatusDay::withinRiskWindow).
     *
     * @param  array<int>  $studentIds
     * @return array<int, array{moods: array<int, string>, lastLog: string|null}>
     */
    private function atRiskSummaries(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }

        return StatusDay::entriesForStudents($studentIds, null)
            ->map(function (Collection $entries): array {
                $warningEntries = $entries->filter(
                    fn(StatusDay $entry): bool => self::isWarning($entry->mood)
                );

                return [
                    'moods' => $warningEntries
                        ->groupBy(fn(StatusDay $entry): string => $entry->mood->value)
                        ->map(fn(Collection $group): int => $group->count())
                        ->sortDesc()
                        ->keys()
                        ->all(),

                    'lastLog' => $entries->first()?->date?->diffForHumans(),
                ];
            })
            ->all();
    }
}