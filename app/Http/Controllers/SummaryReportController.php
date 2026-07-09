<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AppointmentException;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\SummaryReportFilterRequest;
use App\Models\Student;
use App\Services\AppointmentManager;
use App\Services\SummaryReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SummaryReportController extends Controller
{
    public function __construct(
        private readonly AppointmentManager $scheduler,
        private readonly SummaryReportService $reports,
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

        return Inertia::render('SummaryReports/Program', [
            'detail' => $this->reports->programOverview($program, $filters['period']),
            'filters' => $filters,
        ]);
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

    public function consult(StoreConsultationRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->scheduler->scheduleConsultationForStudent($student, $request->slotId());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Consultation scheduled.');
    }
}