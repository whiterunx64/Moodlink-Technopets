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
        $statPeriod = $this->dashboard->resolveStatPeriod($request->query('statPeriod'));
        $activityTab = $this->dashboard->resolveActivityTab($request->query('activityTab'));

        return Inertia::render('Dashboard', [
            'activity_tab' => $activityTab,
            'mood_logs_today' => $this->dashboard->moodLogsToday(),
            'active_students' => $this->dashboard->activeStudents(),
            'flagged_posts' => $this->dashboard->flaggedPostsToday(),
            'escalation_requests' => $this->dashboard->escalationRequests(),
            'mood_entries' => $this->dashboard->moodEntries(),
            'flagged_mood_entries' => $this->dashboard->flaggedMoodEntries(),
            'appointments' => $this->dashboard->upcomingAppointments(),
            'mood_trends' => $this->dashboard->moodTrends(
                $request->query('trendPeriod'),
                $request->query('trendProgram'),
            ),
            'stat_period' => $statPeriod,
            'mood_logs_breakdown' => $this->dashboard->moodLogsBreakdown($statPeriod),
            'students_breakdown' => $this->dashboard->studentsBreakdown(),
            'posts_breakdown' => $this->dashboard->postsBreakdown($statPeriod),
            'appointments_breakdown' => $this->dashboard->appointmentsBreakdown($statPeriod),
            'activity_appointments' => $this->dashboard->recentActivityAppointments(),
        ]);
    }
}