<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected AdminDashboardService $dashboardService;

    public function __construct(AdminDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(): Response
    {
        return Inertia::render(
            'Dashboard',
            $this->dashboardService->getDashboardData()
        );
    }
}