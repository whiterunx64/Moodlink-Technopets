<?php

declare(strict_types=1);

namespace App\Services\SummaryReport;

use App\Enums\PostMood;
use App\Models\StatusDay;
use App\Models\Student;
use App\Traits\HasFilters;
use Illuminate\Support\Collection;

class ProgramReporter
{
    use HasFilters;

    public function __construct(
        private readonly SummaryReportGuard $guard,
    ) {
    }

    /**
     * @return list<array{program: string, total: int, excited: int, content: int, stressed: int, drained: int, at_risk: int}>
     */
    public function perProgramMoodCounts(string $period): array
    {
        $programMoodRows = StatusDay::StudentMoodSummaryByProgram(
            $this->summaryReportPeriodStart($period),
        );
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

    /**
     * @return array<string, mixed>
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException via the guard when the program is malformed or unknown.
     */
    public function programOverview(string $program, string $period, ?string $search = null): array
    {
        $program = $this->guard->ensureProgramExists($program);

        $moodCounts = StatusDay::moodCountsSince(
            $this->summaryReportPeriodStart($period),
            $program,
        );

        $students = Student::verifiedListByProgram($program, $search);

        // One grouped SQL query for every student's per-mood tallies, instead of
        // hydrating each check-in row and counting in PHP.
        $moodCountsByStudent = StatusDay::moodCountsGroupedByStudent(
            $students->pluck('id')->all(),
        );

        return [
            'program' => $program,
            'total' => $moodCounts->sum(),
            'excited' => $moodCounts->get(PostMood::Excited->value, 0),
            'content' => $moodCounts->get(PostMood::Content->value, 0),
            'stressed' => $moodCounts->get(PostMood::Stressed->value, 0),
            'drained' => $moodCounts->get(PostMood::Drained->value, 0),
            'at_risk' => Student::atRiskCountForProgram($program),
            'students' => $students
                ->map(fn(Student $student): array => $this->studentRow($student, $moodCountsByStudent))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, object>  $moodCountsByStudent  keyed by account_id
     * @return array{id: int, name: string, initials: string, student_number: string, year_level: string, trend: string, at_risk: bool, mood_counts: array{Excited: int, Content: int, Stressed: int, Drained: int}}
     */
    private function studentRow(Student $student, Collection $moodCountsByStudent): array
    {
        $counts = $moodCountsByStudent->get($student->id);
        $atRisk = $student->risk_start_date !== null;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'initials' => $student->studentNameInitials,
            'student_number' => $student->student_number,
            'year_level' => $student->year_level_label,
            'trend' => $atRisk ? 'Declining' : 'Stable',
            'at_risk' => $atRisk,
            'mood_counts' => [
                'Excited' => (int) ($counts->excited ?? 0),
                'Content' => (int) ($counts->content ?? 0),
                'Stressed' => (int) ($counts->stressed ?? 0),
                'Drained' => (int) ($counts->drained ?? 0),
            ],
        ];
    }
}
