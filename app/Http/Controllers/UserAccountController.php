<?php

namespace App\Http\Controllers;

use App\Exceptions\CircuitBreakerException;
use App\Http\Requests\RegisterStudentRequest;
use App\Http\Requests\UserAccountFilterRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
use DomainException;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     * Reject a pending student's registration by permanently deleting it.
     */
    public function unverify(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->rejectStudent($student);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        // Security audit log for GCU admin actions
        $admin = $request->user();
        Log::channel('audit')->info('student_account.rejected', [
            'event' => 'student_account.rejected',
            'who' => [
                'actor_type' => 'admin',
                'actor_id' => $admin?->id,
                'actor_email' => $admin?->email,
            ],
            'what' => [
                'description' => 'Administrator rejected student registration.',
                'target_type' => 'student',
                'target_id' => $student->id,
                'student_number' => $student->student_number,
                'student_name' => $student->name,
            ],
            'where' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'context' => [
                'category' => 'student_management',
                'status' => 'rejected',
            ],
            'when' => now()->toIso8601String(),
        ]);

        return back()->with(
            'flash_success',
            'Student registration has been rejected and the record removed.'
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
        try {
            $credentials = $this->service->createSupabaseAccountForStudent($student);
        } catch (CircuitBreakerException $exception) {
            return back()->with(
                'flash_error',
                'The account service is temporarily unavailable because of repeated connection problems. Please wait about a minute and try again. If the issue persists, contact your system administrator.'
            );
        } catch (DomainException $exception) {
            // Business-rule violation
            return back()->withErrors(['register' => $exception->getMessage()]);
        } catch (Exception $exception) {
            // Most commonly an invalid/rejected email from the account provider
            return back()->withErrors([
                'register' => $exception->getMessage() . ' Please ask the student to provide a valid email address.',
            ]);
        }

        // Security audit log for GCU admin actions
        $admin = $request->user();
        Log::channel('audit')->info('student_account.created', [
            'event' => 'student_account.created',
            'who' => [
                'actor_type' => 'admin',
                'actor_id' => $admin?->id,
                'actor_email' => $admin?->email,
            ],
            'what' => [
                'description' => 'Administrator created a new student authentication account.',
                'target_type' => 'student',
                'target_id' => $student->id,
                'student_number' => $student->student_number,
                'student_name' => $student->name,
            ],
            'where' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'context' => [
                'purpose' => 'Student account provisioning',
                'provider' => 'supabase',
                'login_email' => $credentials['email'],
            ],
            'when' => now()->toIso8601String(),
        ]);

        return back()
            ->with('flash_success', 'Supabase account created and student verified.')
            ->with('flash_student_credentials', $credentials);
    }
}