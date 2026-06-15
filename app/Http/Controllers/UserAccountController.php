<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterStudentRequest;
use App\Http\Requests\UserAccountFilterRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
use DomainException;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly StudentAccountService $service,
    ) {
    }

    public function index(UserAccountFilterRequest $request): Response
    {
        $filters = $request->filters();

        $students = Student::paginatedListWithFilters($filters);

        $tabCounts = Student::countsByTab($filters);

        return Inertia::render('UserAccounts/Index', [
            'students' => $students,
            'tabCounts' => $tabCounts,
            'filters' => $filters,
        ]);
    }

    /**
     * Verify an unverified student.
     */
    public function verify(Student $student): RedirectResponse
    {
        try {
            $this->service->verifyStudent($student);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student has been verified and marked as active.'
        );
    }

    /**
     * Set a pending student's status to Unverified.
     */
    public function unverify(Student $student): RedirectResponse
    {
        try {
            $this->service->unverifyStudent($student);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student has been set to unverified status and removed from verified listings.'
        );
    }

    /**
     * Suspend a verified student's account.
     */
    public function suspend(Student $student): RedirectResponse
    {
        try {
            $this->service->suspendStudent($student);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student account has been suspended and access has been restricted.'
        );
    }

    /**
     * Reactivate a suspended student's account.
     */
    public function reactivate(Student $student): RedirectResponse
    {
        try {
            $this->service->reactivateStudent($student);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student account has been reactivated and restored to active status.'
        );
    }

    /**
     * Register a Supabase account for a student.
     *
     * @throws DomainException
     * @throws Exception
     */
    public function register(
        RegisterStudentRequest $request,
        Student $student,
    ): RedirectResponse {
        $email = $request->validated('email');
        $password = $request->validated('password');

        try {
            $this->service->createSupabaseAccountForStudent(
                student: $student,
                email: $email,
                password: $password,
            );
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        } catch (Exception $exception) {
            return back()->with('flash_error', 'Supabase registration failed: ' . $exception->getMessage());
        }

        return back()->with('flash_success', 'Supabase account created and student verified.');
    }
}
