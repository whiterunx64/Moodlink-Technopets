<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $appointments = $this->loadAppointmentsForTab($tab);

        $tabCounts = Appointment::getCountsPerStatusTab();

        $availableSlots = $this->loadAvailableSlots();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'tabCounts' => $tabCounts,
            'availableSlots' => $availableSlots,
            'filters' => [
                'tab' => $tab,
            ],
        ]);
    }

    public function approve(Appointment $appointment): RedirectResponse
    {
        $this->service->approve($appointment);

        return back();
    }

    public function deny(Appointment $appointment): RedirectResponse
    {
        $this->service->deny($appointment);

        return back();
    }

    /**
     * Mark an appointment as completed.
     */
    public function complete(Appointment $appointment): RedirectResponse
    {
        $this->service->complete($appointment);

        return back();
    }


    /**
     * Store a new available schedule slot.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $scheduledAt = "{$validated['date']} {$validated['start_time']}:00";

        $this->service->addSlot($scheduledAt);

        return back();
    }

    public function destroySchedule(AvailableSchedule $schedule): RedirectResponse
    {
        $this->service->deleteSlot($schedule);

        return back();
    }

    /**
     * Query and shape appointments for the given tab, ordered by datetime.
     */
    private function loadAppointmentsForTab(string $tab): Collection
    {
        return Appointment::query()
            ->with('student')
            ->forTab($tab)
            ->orderBy('datetime')
            ->get()
            ->map(fn(Appointment $appointment) => $appointment->toListRow());
    }

    /**
     * Query all untaken schedule slots, ordered by datetime.
     */
    private function loadAvailableSlots(): Collection
    {
        return AvailableSchedule::query()
            ->available()
            ->get()
            ->map(fn(AvailableSchedule $schedule) => $schedule->toSlotData());
    }
}