<?php

namespace App\Http\Controllers;

use App\Exceptions\CircuitBreakerException;
use App\Exceptions\StudentAccountException;
use App\Http\Requests\UserAccountFilterRequest;
use App\Models\Student;
use App\Services\UserAccount\AuditLogger;
use App\Services\UserAccountManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly UserAccountManager $service,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(UserAccountFilterRequest $request): Response
    {
        $studentFilters = $request->filters();

        return Inertia::render('UserAccounts/Index', [
            'students' => $this->service->paginatedStudentList($studentFilters),
            'tabCounts' => $this->service->tabCounts($studentFilters),
            'filters' => $studentFilters,
        ]);
    }

    public function acceptRegistration(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->verifyStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        $this->audit->accountAccepted($request, $student);

        return back()->with(
            'flash_success',
            'Student registration has been accepted and marked as verified.'
        );
    }

    public function rejectRegistration(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->rejectStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        $this->audit->registrationRejected($request, $student);

        return back()->with(
            'flash_success',
            'Student registration has been rejected and the record removed.'
        );
    }

    public function restrictAccess(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->suspendStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        $this->audit->accessRestricted($request, $student);

        return back()->with(
            'flash_success',
            'Student account has been suspended and access has been restricted.'
        );
    }

    public function restoreAccess(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->reactivateStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        $this->audit->accessRestored($request, $student);

        return back()->with(
            'flash_success',
            'Student account has been reactivated and restored to active status.'
        );
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->deleteStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        } catch (QueryException $exception) {
            Log::error('Failed to delete student account.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with(
                'flash_error',
                'The student account could not be deleted because of a server error. Please try again, or contact your system administrator if the problem persists.',
            );
        }

        $this->audit->accountDeleted($request, $student);

        return back()->with('flash_success', 'Student account has been permanently deleted.');
    }

    public function createSupabaseAccount(Request $request, Student $student): RedirectResponse
    {
        try {
            $credentials = $this->service->registerStudent($student);
        } catch (CircuitBreakerException $exception) {
            // External account service is temporarily unavailable.
            return back()->withErrors([
                'register' => 'The account service is temporarily unavailable because of repeated connection problems. Please wait about a minute and try again. If the issue persists, contact your system administrator.',
            ]);
        } catch (StudentAccountException $exception) {
            // Show a safe business validation message.
            return back()->withErrors([
                'register' => $exception->getMessage(),
            ]);
        } catch (QueryException $exception) {
            // Database error after account creation.
            Log::error('Failed to persist student account during registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'register' => 'The account could not be saved because of a server error. The account may have been partially created — please contact your system administrator before retrying.',
            ]);
        } catch (Throwable $exception) {
            // Unexpected error.
            Log::error('Unexpected error during student account registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'register' => 'An unexpected error occurred while creating the account. Please try again, or contact your system administrator if the problem persists.',
            ]);
        }

        $this->audit->accountCreated($request, $student, $credentials);

        return back()
            ->with('flash_success', 'Supabase account created and student verified.')
            ->with('flash_student_credentials', [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);
    }
}