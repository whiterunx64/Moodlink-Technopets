<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppointmentException;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\SummaryReportFilterRequest;
use App\Models\Appointment;
use App\Models\Post;
use App\Models\Student;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use function in_array;

class SummaryReportController extends Controller
{
    public function __construct(
        private readonly AppointmentService $service,
    ) {
    }

    public function index(SummaryReportFilterRequest $request): Response
    {
        $filters = $request->filters();
        $period = $filters['period'];

        $props = ['filters' => $filters];

        if ($filters['tab'] === 'sections') {
            $props['sections'] = $this->perSectionMoodCounts($period);
        } elseif ($filters['tab'] === 'at-risk') {
            $props['atRiskStudents'] = $this->atRiskStudents($period);
        } else {
            $props['overview'] = $this->overviewMoodStatistics($period);
        }

        return Inertia::render('SummaryReports/Index', $props);
    }

    public function showSectionAggregatedReport(SummaryReportFilterRequest $request, string $section): Response
    {
        $filters = $request->filters();

        return Inertia::render('SummaryReports/Section', [
            'detail' => $this->sectionMoodAndStudents($section, $filters['period']),
            'filters' => $filters,
        ]);
    }

    public function showStudentReport(SummaryReportFilterRequest $request, int $studentId): Response
    {
        $filters = $request->filters();
        $trendDays = in_array((int) $request->query('trendDays'), [7, 30]) ? (int) $request->query('trendDays') : 7;

        return Inertia::render('SummaryReports/Student', [
            'studentReport' => $this->studentMoodTrendAndLogs($studentId, $trendDays),
            'filters' => array_merge($filters, ['trendDays' => $trendDays]),
        ]);
    }

    public function consult(StoreConsultationRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->service->scheduleConsultationForStudent($student, $request->scheduledAt());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Consultation scheduled.');
    }

    // ── Private Data ──────────────────────────────────────────────────────────

    private function overviewMoodStatistics(string $period): array
    {
        $moodDistribution = Post::getMoodDistribution($period); // Retrieve mood breakdown.
        $totalMoodLogs = array_sum(array_column($moodDistribution, 'count')); // Calculate total mood entries.

        return [
            'totalMoodLogs' => $totalMoodLogs,
            'avgDailyLogs' => Post::getAvgDailyLogs($period, $totalMoodLogs),
            'atRiskStudents' => Student::getAtRiskCount($period),
            'appointmentsSet' => Appointment::getScheduledCount($period),
            'distribution' => $moodDistribution,
        ];
    }

    private function perSectionMoodCounts(string $period): array
    {
        return [
            ['section' => 'DW31', 'total' => 88, 'excited' => 38, 'content' => 22, 'stressed' => 15, 'drained' => 13, 'atRisk' => 2],
            ['section' => 'DX30', 'total' => 72, 'excited' => 28, 'content' => 20, 'stressed' => 14, 'drained' => 10, 'atRisk' => 1],
            ['section' => 'DX31A', 'total' => 88, 'excited' => 35, 'content' => 25, 'stressed' => 16, 'drained' => 12, 'atRisk' => 2],
        ];
    }

    private function atRiskStudents(string $period): array
    {
        $students = Student::atRiskList($period); // Find at-risk students.
        $studentIds = $students->pluck('id')->all(); // Collect student IDs.

        $summaries = Post::getAtRiskSummary($studentIds, $period); // Load mood summaries.
        $consultationIds = Appointment::activeConsultationStudentIds($studentIds); // Load active consultations.

        return $students->map(function (Student $student) use ($summaries, $consultationIds): array {
            $summary = $summaries[$student->id] ?? ['moods' => [], 'daysAtRisk' => 0, 'lastLog' => null];

            return [
                'id' => $student->id,
                'name' => $student->name,
                'studentNumber' => $student->student_number,
                'section' => $student->section,
                'moods' => $summary['moods'],
                'daysAtRisk' => $summary['daysAtRisk'],
                'lastLog' => $summary['lastLog'],
                'hasConsultation' => in_array($student->id, $consultationIds, true),
            ];
        })->all();
    }

    private function sectionMoodAndStudents(string $section, string $period): array
    {
        return [
            'section' => $section,
            'total' => 88,
            'excited' => 38,
            'content' => 22,
            'stressed' => 15,
            'drained' => 13,
            'atRisk' => 2,
            'students' => [
                ['id' => 1, 'name' => 'Dela Cruz, Juan', 'initials' => 'JD', 'studentNumber' => '202610139', 'yearLevel' => '3rd Year', 'trend' => 'Declining'],
                ['id' => 3, 'name' => 'Santos, Maria', 'initials' => 'MS', 'studentNumber' => '202610172', 'yearLevel' => '2nd Year', 'trend' => 'Stable'],
            ],
        ];
    }

    private function studentMoodTrendAndLogs(int $studentId, int $trendDays): array
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
            'id' => $studentId,
            'name' => 'Dela Cruz, Juan',
            'fullName' => 'Anonymous Tabayoyon',
            'studentNumber' => '202610139',
            'yearLevel' => '3rd Year',
            'section' => 'DW31',
            'initials' => 'JD',
            'moodSummary' => ['excited' => 1, 'content' => 1, 'stressed' => 3, 'drained' => 2],
            'summaryStats' => ['totalMoodLogs' => 7, 'totalPosts' => 3, 'flaggedPosts' => 1],
            'trend' => 'Declining',
            'trendData' => $trendData,
            'recentLogs' => [
                ['id' => 1, 'mood' => 'Stressed', 'content' => 'Worried about the upcoming exams', 'date' => 'Mar 9, 2026'],
                ['id' => 2, 'mood' => 'Drained', 'content' => 'Feeling overwhelmed with the workload', 'date' => 'Mar 8, 2026'],
                ['id' => 3, 'mood' => 'Stressed', 'content' => 'Had a difficult group project meeting', 'date' => 'Mar 7, 2026'],
                ['id' => 4, 'mood' => 'Content', 'content' => 'Finished a task ahead of deadline', 'date' => 'Mar 6, 2026'],
                ['id' => 5, 'mood' => 'Drained', 'content' => 'Tired after a long day of classes', 'date' => 'Mar 5, 2026'],
            ],
        ];
    }
}