<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Facades\SupabaseAuth;
use App\Http\Requests\RegisterStudentRequest;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly StudentAccountService $students,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'year_level' => $request->integer('year_level') ?: null,
            'tab' => $request->string('tab')->toString() ?: 'All',
        ];

        return Inertia::render('UserAccounts/Index', [
            'students' => $this->students->paginateForAdmin($filters),
            'tabCounts' => $this->students->tabCounts($filters),
            'filters' => $filters,
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
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back();
    }
    public function register(RegisterStudentRequest $request, Student $student): RedirectResponse
    {
        if ($student->status !== StudentStatus::Pending) {
            return back()->withErrors(['status' => 'Only pending students can be registered.']);
        }

        try {
            SupabaseAuth::signUp(
                email: $request->validated('email'),
                password: $request->validated('password'),
                data: [
                    'student_id' => $student->id,
                    'name' => $student->name,
                ],
            );

            $this->students->changeStatus($student, StudentStatus::Verified);

        } catch (Exception $e) {
            return back()->withErrors(['email' => 'Supabase error: ' . $e->getMessage()]);
        }

        return back();
    }
}

