<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AvailableSchedule;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
  public function __construct(
    private readonly AppointmentService $appointments,
  ) {
  }

  public function index(Request $request): Response
  {
    $tab = $request->string('tab')->toString() ?: 'requests';

    return Inertia::render('Appointments/Index', [
      'appointments' => $this->appointments->getByTab($tab),
      'tabCounts' => $this->appointments->tabCounts(),
      'availableSlots' => $this->appointments->availableSlots(),
      'filters' => ['tab' => $tab],
    ]);
  }

  public function approve(Appointment $appointment): RedirectResponse
  {
    $this->appointments->approve($appointment);
    return back();
  }

  public function deny(Appointment $appointment): RedirectResponse
  {
    $this->appointments->deny($appointment);
    return back();
  }

  public function complete(Appointment $appointment): RedirectResponse
  {
    $this->appointments->complete($appointment);
    return back();
  }

  public function storeSchedule(Request $request): RedirectResponse
  {
    $request->validate([
      'date' => ['required', 'date'],
      'start_time' => ['required', 'date_format:H:i'],
    ]);

    $datetime = $request->date . ' ' . $request->start_time . ':00';
    $this->appointments->addSlot($datetime);

    return back();
  }

  public function destroySchedule(AvailableSchedule $schedule): RedirectResponse
  {
    $this->appointments->deleteSlot($schedule);
    return back();
  }
}