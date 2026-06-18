<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentFilterRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Services\AppointmentService;
use DomainException;
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
        $appointments = Appointment::getListForTab($tab);
        $tabCounts = Appointment::getCountsPerStatusTab();
        $availableSlots = AvailableSchedule::getAvailableSlotsList();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'tabCounts' => $tabCounts,
            'availableSlots' => $availableSlots,
            'filters' => ['tab' => $tab],
        ]);
    }

    public function approve(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->approve($appointment);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment approved.');
    }

    public function reject(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->reject($appointment);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment rejected.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->complete($appointment);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment marked as completed.');
    }

    public function storeSchedule(StoreScheduleRequest $request): RedirectResponse
    {
        try {
            $this->service->addSlot($request->scheduledAt());
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot added.');
    }

    public function destroySchedule(AvailableSchedule $schedule): RedirectResponse
    {
        try {
            $this->service->deleteSlot($schedule);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot removed.');
    }
}