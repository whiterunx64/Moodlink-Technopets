<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppointmentException;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\SummaryReportFilterRequest;
use App\Models\Student;
use App\Services\AppointmentManager;
use App\Services\SummaryReportsManager;
use App\Support\PhTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SummaryReportController extends Controller
{
    public function __construct(
        private readonly AppointmentManager $scheduler,
        private readonly SummaryReportsManager $reports,
    ) {
    }

    public function index(SummaryReportFilterRequest $request): Response
    {
        $filters = $request->filters();
        $period = $filters['period'];

        $props = ['filters' => $filters];

        if ($filters['tab'] === 'programs') {
            $props['programs'] = $this->reports->perProgramMoodCounts($period);
        } elseif ($filters['tab'] === 'studentsOfConcern') {
            $props['atRiskStudents'] = $this->reports->atRiskStudents();
            $props['availableSlots'] = $this->scheduler->openConsultationSlots();
        } else {
            $props['overview'] = $this->reports->overview($period);
        }

        return Inertia::render('SummaryReports/Index', $props);
    }

    public function showProgram(SummaryReportFilterRequest $request, string $program): Response
    {
        $filters = $request->filters();
        $search = $request->searchTerm();

        return Inertia::render('SummaryReports/Program', [
            'detail' => $this->reports->programOverview($program, $filters['period'], $search),
            'filters' => array_merge($filters, ['search' => $search]),
        ]);
    }

    public function exportProgramPdf(SummaryReportFilterRequest $request, string $program): HttpResponse
    {
        $filters = $request->filters();
        // Export the full program roster regardless of any on-screen search.
        $detail = $this->reports->programOverview($program, $filters['period']);

        $periodLabel = [
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'all_time' => 'All Time',
        ][$filters['period']] ?? ucfirst((string) $filters['period']);

        Log::channel(config('supabase-auth.monitoring.logging.channel'))->info('Program mood report exported', [
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'program' => $program,
            'period' => $filters['period'],
            'student_count' => \count($detail['students']),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $pdf = Pdf::loadView('pdf.program-mood-report', [
                'detail' => $detail,
                'periodLabel' => $periodLabel,
                'generatedAt' => PhTime::now()->format('M j, Y g:i A'),
            ])->setPaper('a4', 'portrait');

            $this->hardenPdf($pdf);

            $safeProgram = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $program) ?: 'program';

            return $this->streamPdf($pdf, "program-report-{$safeProgram}.pdf");
        } catch (\Throwable $e) {
            return $this->pdfFailure($e, ['program' => $program]);
        }
    }

    public function showStudent(SummaryReportFilterRequest $request, Student $student): Response
    {
        $filters = $request->filters();
        $trendDays = $request->trendDays();

        $from = $request->query('from') === 'concern' ? 'concern' : 'program';

        return Inertia::render('SummaryReports/Student', [
            'studentReport' => $this->reports->studentMoodReport($student, $trendDays),
            'filters' => array_merge($filters, ['trendDays' => $trendDays]),
            'from' => $from,
        ]);
    }

    public function exportStudentPdf(SummaryReportFilterRequest $request, Student $student): HttpResponse
    {
        $trendDays = $request->trendDays();
        $report = $this->reports->studentMoodReport($student, $trendDays);

        $moods = ['Excited', 'Content', 'Stressed', 'Drained'];
        $trendRows = array_map(function (array $day) use ($moods): array {
            $counts = array_fill_keys($moods, 0);
            foreach ($day['posts'] as $post) {
                if (isset($counts[$post['mood']])) {
                    $counts[$post['mood']]++;
                }
            }

            return [
                'date' => $day['date'],
                'counts' => $counts,
                'total' => count($day['posts']),
            ];
        }, $report['trend_data']);

        Log::channel(config('supabase-auth.monitoring.logging.channel'))->info('Student mood report exported', [
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'period' => $request->filters()['period'],
            'trend_days' => $trendDays,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $pdf = Pdf::loadView('pdf.student-mood-report', [
                'report' => $report,
                'trendRows' => $trendRows,
                'moods' => $moods,
                'trendDays' => $trendDays,
                'period' => $request->filters()['period'],
                'generatedAt' => PhTime::now()->format('M j, Y g:i A'),
            ])->setPaper('a4', 'portrait');

            $this->hardenPdf($pdf);

            $safeNumber = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $student->student_number) ?: 'student';

            return $this->streamPdf($pdf, "mood-report-{$safeNumber}.pdf");
        } catch (\Throwable $e) {
            return $this->pdfFailure($e, ['student_id' => $student->id]);
        }
    }

    public function consult(StoreConsultationRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->scheduler->scheduleConsultationForStudent($student, $request->slotId());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Consultation scheduled.');
    }

    /**
     * Lock down the PDF renderer so student-controlled text can't fetch remote
     * resources or execute PHP/JS during rendering.
     */
    private function hardenPdf(\Barryvdh\DomPDF\PDF $pdf): void
    {
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isPhpEnabled', false);
        $pdf->setOption('isJavascriptEnabled', false);
    }

    private function streamPdf(\Barryvdh\DomPDF\PDF $pdf, string $filename): HttpResponse
    {
        return $pdf->download($filename)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function pdfFailure(\Throwable $e, array $context): HttpResponse
    {
        Log::channel(config('supabase-auth.monitoring.logging.channel'))->error('Report PDF generation failed', [
            ...$context,
            'error' => $e->getMessage(),
        ]);

        abort(500, 'We could not generate that report right now. Please try again.');
    }
}