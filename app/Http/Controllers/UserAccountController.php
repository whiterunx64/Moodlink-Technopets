<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function index(): Response
    {
        // Template-only: the page renders its own mock data for now.
        // Pass real student data here once the service/query is ready.
        return Inertia::render('UserAccounts');
    }
}
