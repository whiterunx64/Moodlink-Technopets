<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\YearLevel;
use App\Http\Requests\AppointmentFilterRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Exceptions\AppointmentException;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $service,
    ) {
    }

    public function index(AppointmentFilterRequest $request): Response
    {
        $tab = $request->filters()['tab'];

        $appointments = Appointment::getListForTab($tab)
            ->map(fn(Appointment $appointment): array => [
                'id' => $appointment->id,
                'student_id' => $appointment->student_id,
                'context' => $appointment->context,
                'note' => $appointment->note,
                'status' => $appointment->status->value,
                'date' => $appointment->display_date,
                'time' => $appointment->display_time,
                'student_name' => $appointment->student_name,
                'section' => $appointment->student_section,
                'student_profile' => $this->getStudentInformation($appointment),
            ]);

        $counts = Appointment::countsByStatus();

        $tabCounts = [
            'requests' => $counts[AppointmentStatus::Pending->value] ?? 0,
            'scheduled' => $counts[AppointmentStatus::Scheduled->value] ?? 0,
            'history' => ($counts[AppointmentStatus::Completed->value] ?? 0)
                + ($counts[AppointmentStatus::Rejected->value] ?? 0),
            'rejected' => $counts[AppointmentStatus::Rejected->value] ?? 0,
        ];

        $availableSlots = AvailableSchedule::getAvailableSlotsList()
            ->map(fn(AvailableSchedule $schedule): array => [
                'id' => $schedule->id,
                'date' => $schedule->display_date,
                'start_time' => $schedule->display_time,
                'taken' => $schedule->takenBy !== null,
            ]);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'tabCounts' => $tabCounts,
            'availableSlots' => $availableSlots,
            'filters' => ['tab' => $tab],
        ]);
    }

    public function approveAppointmentRequest(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->approve($appointment);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment approved.');
    }

    public function rejectAppointmentRequest(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->reject($appointment);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment rejected.');
    }

    public function markAppointmentAsCompleted(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->complete($appointment);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment marked as completed.');
    }

    public function createScheduleSlot(StoreScheduleRequest $request): RedirectResponse
    {
        try {
            $this->service->addSlot($request->scheduledAt());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot added.');
    }

    public function destroyScheduleSlot(AvailableSchedule $schedule): RedirectResponse
    {
        try {
            $this->service->deleteSlot($schedule);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function getStudentInformation(Appointment $appointment): array
    {
        $student = $appointment->student;

        if ($student === null) {
            return [
                'initials' => '',
                'section' => '',
                'year_level' => '',
                'student_id' => '',
                'total_appointments' => 0,
                'history' => [],
            ];
        }

        return [
            'initials' => $student->studentNameInitials,
            'section' => $student->section,
            'year_level' => YearLevel::tryFrom($student->year_level)?->toOrdinal() ?? '',
            'student_id' => $student->student_number,
            'total_appointments' => $student->appointments->count(),
            'history' => $student->appointments
                ->sortByDesc('datetime')
                ->map(fn(Appointment $appointment): array => [
                    'context' => $appointment->context,
                    'date' => $appointment->display_date,
                    'time' => $appointment->display_time,
                    'note' => $appointment->note,
                    'status' => $appointment->status->value,
                ])
                ->values()
                ->all(),
        ];
    }
}