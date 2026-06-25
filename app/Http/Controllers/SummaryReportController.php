<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppointmentException;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\SummaryReportFilterRequest;
use App\Models\Student;
use App\Services\AppointmentService;
use App\Services\SummaryReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SummaryReportController extends Controller
{
    public function __construct(
        private readonly AppointmentService $service,
        private readonly SummaryReportService $report,
    ) {
    }

    public function index(SummaryReportFilterRequest $request): Response
    {
        $filters = $request->filters();
        $period = $filters['period'];

        $props = ['filters' => $filters];

        if ($filters['tab'] === 'programs') {
            $props['programs'] = $this->perProgramMoodCounts($period);
        } elseif ($filters['tab'] === 'at-risk') {
            $props['atRiskStudents'] = $this->report->atRiskStudents();
        } else {
            $props['overview'] = $this->report->overview($period);
        }

        return Inertia::render('SummaryReports/Index', $props);
    }

    public function showProgram(SummaryReportFilterRequest $request, string $program): Response
    {
        $filters = $request->filters();

        return Inertia::render('SummaryReports/Program', [
            'detail' => $this->programMoodAndStudents($program, $filters['period']),
            'filters' => $filters,
        ]);
    }

    public function showStudent(SummaryReportFilterRequest $request, string $studentUuid): Response
    {
        $student = Student::findBySupabaseAuthId($studentUuid) ?? abort(404);

        $filters = $request->filters();
        $trendDays = $request->trendDays();

        return Inertia::render('SummaryReports/Student', [
            'studentReport' => $this->studentMoodTrendAndLogs($student, $trendDays),
            'filters' => array_merge($filters, ['trendDays' => $trendDays]),
        ]);
    }

    public function consult(StoreConsultationRequest $request, string $studentUuid): RedirectResponse
    {
        $student = Student::findBySupabaseAuthId($studentUuid) ?? abort(404);

        try {
            $this->service->scheduleConsultationForStudent($student, $request->scheduledAt());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Consultation scheduled.');
    }

    // ── Placeholder data (no real source yet) ──────────────────────────────────

    private function perProgramMoodCounts(string $period): array
    {
        return [
            ['program' => 'BSIT', 'total' => 88, 'excited' => 38, 'content' => 22, 'stressed' => 15, 'drained' => 13, 'at_risk' => 2],
            ['program' => 'BSITWMA', 'total' => 72, 'excited' => 28, 'content' => 20, 'stressed' => 14, 'drained' => 10, 'at_risk' => 1],
            ['program' => 'BSCS', 'total' => 88, 'excited' => 35, 'content' => 25, 'stressed' => 16, 'drained' => 12, 'at_risk' => 2],
        ];
    }

    private function programMoodAndStudents(string $program, string $period): array
    {
        return [
            'program' => $program,
            'total' => 88,
            'excited' => 38,
            'content' => 22,
            'stressed' => 15,
            'drained' => 13,
            'at_risk' => 2,
            'students' => [
                ['id' => 1, 'name' => 'Dela Cruz, Juan', 'initials' => 'JD', 'student_number' => '202610139', 'year_level' => '3rd Year', 'trend' => 'Declining'],
                ['id' => 3, 'name' => 'Santos, Maria', 'initials' => 'MS', 'student_number' => '202610172', 'year_level' => '2nd Year', 'trend' => 'Stable'],
            ],
        ];
    }

    private function studentMoodTrendAndLogs(Student $student, int $trendDays): array
    {
        $trendData = $trendDays === 7
            ? [
                ['label' => 'Mon', 'score' => 3.0],
                ['label' => 'Tue', 'score' => 2.5],
                ['label' => 'Wed', 'score' => 2.5],
                ['label' => 'Thu', 'score' => 2.0],
                ['label' => 'Fri', 'score' => 2.5],
                ['label' => 'Sat', 'score' => 2.0],
                ['label' => 'Sun', 'score' => 2.0],
            ]
            : array_map(fn($i) => ['label' => 'D' . ($i + 1), 'score' => 2.0 + ($i % 4) * 0.3], range(0, 29));

        return [
            'id' => $student->id,
            'name' => 'Dela Cruz, Juan',
            'full_name' => 'Anonymous Tabayoyon',
            'student_number' => '202610139',
            'year_level' => '3rd Year',
            'program' => 'BSITWMA',
            'initials' => 'JD',
            'mood_summary' => ['excited' => 1, 'content' => 1, 'stressed' => 3, 'drained' => 2],
            'summary_stats' => ['total_mood_logs' => 7, 'total_posts' => 3, 'flagged_posts' => 1],
            'trend' => 'Declining',
            'trend_data' => $trendData,
            'recent_logs' => [
                ['id' => 1, 'mood' => 'Stressed', 'content' => 'Worried about the upcoming exams', 'date' => 'Mar 9, 2026'],
                ['id' => 2, 'mood' => 'Drained', 'content' => 'Feeling overwhelmed with the workload', 'date' => 'Mar 8, 2026'],
                ['id' => 3, 'mood' => 'Stressed', 'content' => 'Had a difficult group project meeting', 'date' => 'Mar 7, 2026'],
                ['id' => 4, 'mood' => 'Content', 'content' => 'Finished a task ahead of deadline', 'date' => 'Mar 6, 2026'],
                ['id' => 5, 'mood' => 'Drained', 'content' => 'Tired after a long day of classes', 'date' => 'Mar 5, 2026'],
            ],
        ];
    }
}