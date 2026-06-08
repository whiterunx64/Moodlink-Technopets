<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected AdminDashboardService $dashboardService;

    public function __construct(AdminDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            ...$this->dashboardService->getDashboardData(),
            'moodTrends' => $this->dashboardService->getMoodTrends(
                $request->query('trendPeriod'),
                $request->query('trendSection'),
            ),
        ]);
    }
}