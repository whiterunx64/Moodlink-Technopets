<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'mood_logs_today' => $this->dashboard->moodLogsToday(),
            'active_students' => $this->dashboard->activeStudents(),
            'flagged_posts' => $this->dashboard->flaggedPostsToday(),
            'escalation_requests' => $this->dashboard->escalationRequests(),
            'mood_entries' => $this->dashboard->moodEntries(),
            'appointments' => $this->dashboard->upcomingAppointments(),
            'mood_trends' => $this->dashboard->moodTrends(
                $request->query('trendPeriod'),
                $request->query('trendProgram'),
            ),
        ]);
    }
}