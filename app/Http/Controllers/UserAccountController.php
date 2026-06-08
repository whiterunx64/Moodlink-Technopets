<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly StudentAccountService $students,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search'     => $request->string('search')->toString() ?: null,
            'year_level' => $request->integer('year_level') ?: null,
            'tab'        => $request->string('tab')->toString() ?: 'All',
        ];

        return Inertia::render('UserAccounts', [
            'students'  => $this->students->paginateForAdmin($filters),
            'tabCounts' => $this->students->tabCounts($filters),
            'filters'   => $filters,
        ]);
    }

    /**
     * Verify / reject / suspend / reactivate a student account.
     * The allowed transition is enforced by the StudentStatus enum.
     */
    public function updateStatus(UpdateStudentStatusRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->students->changeStatus($student, $request->targetStatus());
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back();
    }
}
