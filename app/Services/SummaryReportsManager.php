<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Services\SummaryReport\AtRiskReporter;
use App\Services\SummaryReport\OverviewReporter;
use App\Services\SummaryReport\ProgramReporter;
use App\Services\SummaryReport\StudentReporter;

class SummaryReportsManager
{
    public function __construct(
        private readonly OverviewReporter $overview,
        private readonly ProgramReporter $program,
        private readonly AtRiskReporter $atRisk,
        private readonly StudentReporter $student,
    ) {
    }

    public function overview(string $period): array
    {
        return $this->overview->build($period);
    }

    /**
     * @return list<array{program: string, total: int, excited: int, content: int, stressed: int, drained: int, at_risk: int}>
     */
    public function perProgramMoodCounts(string $period): array
    {
        return $this->program->perProgramMoodCounts($period);
    }

    /**
     * @return array<string, mixed>
     */
    public function programOverview(string $program, string $period, ?string $search = null): array
    {
        return $this->program->programOverview($program, $period, $search);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function atRiskStudents(): array
    {
        return $this->atRisk->build();
    }

    /**
     * @return array<string, mixed>
     */
    public function studentMoodReport(Student $student, int $trendDays): array
    {
        return $this->student->build($student, $trendDays);
    }
}
