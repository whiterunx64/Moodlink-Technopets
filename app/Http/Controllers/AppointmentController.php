<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Services\AppointmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $tab = $request->string('tab')->toString() ?: 'requests';
        $appointments   = Appointment::getListForTab($tab);
        $tabCounts      = Appointment::getCountsPerStatusTab();
        $availableSlots = AvailableSchedule::getAvailableSlotsList();

        return Inertia::render('Appointments/Index', [
            'appointments'   => $appointments,
            'tabCounts'      => $tabCounts,
            'availableSlots' => $availableSlots,
            'filters'        => ['tab' => $tab],
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

    public function deny(Appointment $appointment): RedirectResponse
    {
        try {
            $this->service->deny($appointment);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Appointment denied.');
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

    /**
     * Store a new available schedule slot.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $scheduledAt = "{$validated['date']} {$validated['start_time']}:00";

        try {
            $this->service->addSlot($scheduledAt);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot added.');
    }

    /**
     * Delete an available schedule slot.
     */
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
