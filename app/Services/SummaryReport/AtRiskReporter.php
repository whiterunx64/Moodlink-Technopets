<?php

declare(strict_types=1);

namespace App\Services\SummaryReport;

use App\Models\StatusDay;
use App\Models\Student;
use App\Services\RiskMonitor;
use App\Support\PhTime;
use Illuminate\Support\Collection;

class AtRiskReporter
{
    public function __construct(
        private readonly RiskMonitor $risk,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(): array
    {
        $students = Student::flaggedAtRiskList();
        $studentIds = $students->pluck('id')->all();

        $checkInsByStudentId = StatusDay::moodCheckInsForStudents($studentIds);
        // Regex crisis-language scan over journals — one grouped query for all.
        $crisisByStudentId = StatusDay::crisisSignalsForStudents($studentIds);

        return $students
            ->map(function (Student $student) use ($checkInsByStudentId, $crisisByStudentId): array {
                /** @var Collection<int, StatusDay> $checkIns */
                $checkIns = $checkInsByStudentId->get($student->id) ?? new Collection();
                $windows = $this->risk->calculateWindows($checkIns);
                $warningMoodCounts = $this->risk->warningMoodCountsByFrequency($checkIns);
                $crisis = $crisisByStudentId->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_number' => $student->student_number,
                    'program' => $student->program,
                    'moods' => $warningMoodCounts->keys()->all(),
                    'last_log' => $checkIns->last()?->date?->diffForHumans(),
                    'window_a_count' => $windows['window_a'],
                    'warning_mood_counts' => $warningMoodCounts->all(),
                    'time_at_risk' => $student->risk_start_date
                        ?->diff(PhTime::now())
                        ->forHumans(parts: 1),
                    // Crisis-language flag from the journal regex scan.
                    'crisis_count' => (int) ($crisis->crisis_count ?? 0),
                    'crisis_excerpt' => $crisis->latest_excerpt ?? null,
                    'has_consultation' => false,
                ];
            })
            // Students with crisis-language journals float to the very top,
            // then by window-A pressure.
            ->sortBy(fn(array $row): array => [-($row['crisis_count'] > 0 ? 1 : 0), -$row['window_a_count']])
            ->values()
            ->all();
    }
}
