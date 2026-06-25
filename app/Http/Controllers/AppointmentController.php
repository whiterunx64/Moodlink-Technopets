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
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
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
                'program' => $appointment->student_program,
                'student_profile' => $this->getStudentInformation($appointment),
                'can_check_in' => $this->service->isWithinCheckInWindow($appointment),
                'checkin_url' => $this->checkInUrl($appointment),
                'checkin_expires_at' => $this->service->checkInWindowEnd($appointment)->toIso8601String(),
            ]);

        $statusCounts = Appointment::tabCounts();

        $tabCounts = [
            'requests' => (int) $statusCounts->requests,
            'scheduled' => (int) $statusCounts->scheduled,
            'history' => (int) $statusCounts->history,
            'rejected' => (int) $statusCounts->rejected,
            'missed' => (int) $statusCounts->missed,
        ];

        $availableSlots = AvailableSchedule::getAvailableSlotsList()
            ->map(fn(AvailableSchedule $schedule): array => [
                'id' => $schedule->id,
                'date' => $schedule->display_date,
                'start_time' => $schedule->display_time,
                'taken' => $schedule->takenBy !== null,
            ]);

        // Sessions currently inside their check-in window, independent of the active tab,
        // so the front-end can auto-surface the QR even when the admin is viewing another tab.
        $checkInReady = Appointment::awaitingCheckIn()->with('student')->get()
            ->filter(fn(Appointment $appointment): bool => $this->service->isWithinCheckInWindow($appointment))
            ->map(fn(Appointment $appointment): array => [
                'id' => $appointment->id,
                'student_name' => $appointment->student_name,
                'date' => $appointment->display_date,
                'time' => $appointment->display_time,
                'checkin_url' => $this->checkInUrl($appointment),
                'checkin_expires_at' => $this->service->checkInWindowEnd($appointment)->toIso8601String(),
            ])
            ->values();

        // TEMP DEBUG — remove once the QR popup is confirmed working.
        $checkInDebug = [
            'now_utc' => \Carbon\Carbon::now('UTC')->toIso8601String(),
            'app_now' => \Carbon\Carbon::now()->toIso8601String(),
            'app_tz' => config('app.timezone'),
            'awaiting_scope_count' => Appointment::awaitingCheckIn()->count(),
            'scheduled' => Appointment::scheduled()->orderByDesc('id')->limit(5)->get()
                ->map(fn(Appointment $a): array => [
                    'id' => $a->id,
                    'raw_datetime' => $a->getRawOriginal('datetime'),
                    'cast_datetime' => $a->datetime->toIso8601String(),
                    'window_end' => $this->service->checkInWindowEnd($a)->toIso8601String(),
                    'in_window' => $this->service->isWithinCheckInWindow($a),
                ])->values(),
        ];

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'tabCounts' => $tabCounts,
            'availableSlots' => $availableSlots,
            'checkInReady' => $checkInReady,
            'checkInDebug' => $checkInDebug,
            'filters' => ['tab' => $tab],
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
        try {
            $this->service->addSlot($request->scheduledAt());
        } catch (AppointmentException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Schedule slot added.');
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

    private function checkInUrl(Appointment $appointment): ?string
    {
        if ($appointment->status !== AppointmentStatus::Scheduled) {
            return null;
        }

        return URL::temporarySignedRoute(
            'appointments.checkin',
            $this->service->checkInWindowEnd($appointment),
            ['appointment' => $appointment->id],
        );
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
                'program' => '',
                'year_level' => '',
                'student_id' => '',
                'total_appointments' => 0,
                'history' => [],
            ];
        }

        return [
            'initials' => $student->studentNameInitials,
            'program' => $student->program,
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