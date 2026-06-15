<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SummaryReportController extends Controller
{
    private const VALID_PERIODS = ['this_week', 'this_month', 'all_time'];

    public function index(Request $request): Response
    {
        $period = $this->validPeriod($request->query('period'));
        $tab    = $request->string('tab')->toString() ?: 'overview';

        return Inertia::render('SummaryReports/Index', [
            'overview'       => $this->mockOverview(),
            'sections'       => $this->mockSections(),
            'atRiskStudents' => $this->mockAtRiskStudents(),
            'filters'        => ['period' => $period, 'tab' => $tab],
        ]);
    }

    public function showSection(Request $request, string $section): Response
    {
        $period = $this->validPeriod($request->query('period'));
        $search = $request->string('search')->toString() ?: null;

        return Inertia::render('SummaryReports/Section', [
            'detail'  => $this->mockSectionDetail($section),
            'filters' => ['period' => $period, 'search' => $search],
        ]);
    }

    public function showStudent(Request $request, int $studentId): Response
    {
        $period     = $this->validPeriod($request->query('period'));
        $trendDays  = in_array((int) $request->query('trendDays'), [7, 30]) ? (int) $request->query('trendDays') : 7;

        return Inertia::render('SummaryReports/Student', [
            'studentReport' => $this->mockStudentReport($studentId, $trendDays),
            'filters'       => ['period' => $period, 'trendDays' => $trendDays],
        ]);
    }

    // ── Mock data ─────────────────────────────────────────────────────────────

    private function mockOverview(): array
    {
        return [
            'totalMoodLogs'   => 248,
            'avgDailyLogs'    => 35,
            'atRiskStudents'  => 9,
            'appointmentsSet' => 6,
            'distribution'    => [
                ['label' => 'Excited',  'count' => 89, 'pct' => 36],
                ['label' => 'Content',  'count' => 69, 'pct' => 28],
                ['label' => 'Stressed', 'count' => 55, 'pct' => 22],
                ['label' => 'Drained',  'count' => 35, 'pct' => 14],
            ],
        ];
    }

    private function mockSections(): array
    {
        return [
            ['section' => 'DW31',  'total' => 88, 'excited' => 38, 'content' => 22, 'stressed' => 15, 'drained' => 13, 'atRisk' => 2],
            ['section' => 'DX30',  'total' => 72, 'excited' => 28, 'content' => 20, 'stressed' => 14, 'drained' => 10, 'atRisk' => 1],
            ['section' => 'DX31A', 'total' => 88, 'excited' => 35, 'content' => 25, 'stressed' => 16, 'drained' => 12, 'atRisk' => 2],
        ];
    }

    private function mockAtRiskStudents(): array
    {
        return [
            ['id' => 1, 'name' => 'Dela Cruz, Juan',  'studentNumber' => '202610139', 'section' => 'DW31',  'moods' => ['Stressed', 'Drained'], 'daysFlagged' => 12, 'lastLog' => '2 hours ago',  'hasConsultation' => false],
            ['id' => 2, 'name' => 'Garcia, Ana',       'studentNumber' => '202610221', 'section' => 'DX31A', 'moods' => ['Drained', 'Stressed'], 'daysFlagged' => 9,  'lastLog' => '5 hours ago',  'hasConsultation' => false],
            ['id' => 3, 'name' => 'Santos, Maria',     'studentNumber' => '202610172', 'section' => 'DW31',  'moods' => ['Stressed'],            'daysFlagged' => 8,  'lastLog' => '1 day ago',    'hasConsultation' => true],
            ['id' => 4, 'name' => 'Lopez, Carlos',     'studentNumber' => '202610250', 'section' => 'DX31A', 'moods' => ['Drained', 'Stressed'], 'daysFlagged' => 7,  'lastLog' => '3 hours ago',  'hasConsultation' => false],
        ];
    }

    private function mockSectionDetail(string $section): array
    {
        return [
            'section'  => $section,
            'total'    => 88,
            'excited'  => 38,
            'content'  => 22,
            'stressed' => 15,
            'drained'  => 13,
            'atRisk'   => 2,
            'students' => [
                ['id' => 1, 'name' => 'Dela Cruz, Juan', 'initials' => 'JD', 'studentNumber' => '202610139', 'yearLevel' => '3rd Year', 'trend' => 'Declining'],
                ['id' => 3, 'name' => 'Santos, Maria',   'initials' => 'MS', 'studentNumber' => '202610172', 'yearLevel' => '2nd Year', 'trend' => 'Stable'],
            ],
        ];
    }

    private function mockStudentReport(int $studentId, int $trendDays): array
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
            : array_map(fn ($i) => ['label' => 'D' . ($i + 1), 'score' => 2.0 + ($i % 4) * 0.3], range(0, 29));

        return [
            'id'            => $studentId,
            'name'          => 'Dela Cruz, Juan',
            'anonymousName' => 'Anonymous Tabayoyon',
            'studentNumber' => '202610139',
            'yearLevel'     => '3rd Year',
            'section'       => 'DW31',
            'initials'      => 'JD',
            'moodSummary'   => ['excited' => 1, 'content' => 1, 'stressed' => 3, 'drained' => 2],
            'summaryStats'  => ['totalMoodLogs' => 7, 'totalPosts' => 3, 'flaggedPosts' => 1],
            'trend'         => 'Declining',
            'trendData'     => $trendData,
            'recentLogs'    => [
                ['id' => 1, 'mood' => 'Stressed', 'content' => 'Worried about the upcoming exams',        'date' => 'Mar 9, 2026'],
                ['id' => 2, 'mood' => 'Drained',  'content' => 'Feeling overwhelmed with the workload',  'date' => 'Mar 8, 2026'],
                ['id' => 3, 'mood' => 'Stressed', 'content' => 'Had a difficult group project meeting',  'date' => 'Mar 7, 2026'],
                ['id' => 4, 'mood' => 'Content',  'content' => 'Finished a task ahead of deadline',      'date' => 'Mar 6, 2026'],
                ['id' => 5, 'mood' => 'Drained',  'content' => 'Tired after a long day of classes',      'date' => 'Mar 5, 2026'],
            ],
        ];
    }

    private function validPeriod(?string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'this_week';
    }
}
