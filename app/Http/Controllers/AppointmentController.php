<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentFilterRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Models\Student;
use App\Exceptions\AppointmentException;
use App\Services\AppointmentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentManager $service,
    ) {
    }

    public function index(AppointmentFilterRequest $request): Response
    {
        $tab = $request->filters()['tab'];
        $studentId = $request->integer('student');

        return Inertia::render('Appointments/Index', [
            'appointments' => $this->service->appointmentListForTab($tab),
            'tabCounts' => $this->service->tabCounts(),
            'availableSlots' => $this->service->availableSlotsList(),
            'checkInReady' => $this->service->checkInReadyList(),
            'filters' => ['tab' => $tab],
            'studentProfile' => Inertia::optional(
                fn(): ?array => $studentId > 0
                    ? $this->service->studentProfileWithHistory(Student::findOrFail($studentId))
                    : null,
            ),
        ]);
    }

    public function approve(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->approve($appointment);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment approved.');
    }

    public function reject(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->reject($appointment);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment rejected.');
    }

    public function checkIn(Appointment $appointment): View
    {
        try {
            $this->service->completeViaCheckIn($appointment);
        } catch (AppointmentException $exception) {
            return view('appointments.checkin-result', [
                'success' => false,
                'message' => $exception->getMessage(),
                'appointment' => $appointment,
            ]);
        }

        return view('appointments.checkin-result', [
            'success' => true,
            'message' => 'Session checked in and marked as completed.',
            'appointment' => $appointment,
        ]);
    }

    public function storeSlot(StoreScheduleRequest $request): RedirectResponse
    {
        $added = 0;
        $errors = [];

        foreach ($request->scheduledDatetimes() as $datetime) {
            try {
                $this->service->addSlot($datetime);
                $added++;
            } catch (AppointmentException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($added === 0) {
            return back()->with('flash_error', $errors[0] ?? 'No slots could be added.');
        }

        $message = $added === 1 ? '1 slot added.' : "{$added} slots added.";

        return back()->with('flash_success', $message);
    }

    public function destroySlot(AvailableSchedule $slot): RedirectResponse
    {
        try {
            $this->service->deleteSlot($slot);
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot removed.');
    }
}