<?php

namespace App\Http\Controllers;

use App\Exceptions\CircuitBreakerException;
use App\Exceptions\InfrastructureException;
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
        } catch (QueryException $exception) {
            Log::error('Failed to accept student registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->with(
                'flash_error',
                $connectionFailure?->getMessage()
                ?? StudentAccountException::statusChangeFailedDueToServerError('accepted', $exception)->getMessage(),
            );
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
        } catch (QueryException $exception) {
            Log::error('Failed to reject student registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->with(
                'flash_error',
                $connectionFailure?->getMessage()
                ?? StudentAccountException::statusChangeFailedDueToServerError('rejected', $exception)->getMessage(),
            );
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
        } catch (QueryException $exception) {
            Log::error('Failed to suspend student account.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->with(
                'flash_error',
                $connectionFailure?->getMessage()
                ?? StudentAccountException::statusChangeFailedDueToServerError('suspended', $exception)->getMessage(),
            );
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
        } catch (QueryException $exception) {
            Log::error('Failed to reactivate student account.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->with(
                'flash_error',
                $connectionFailure?->getMessage()
                ?? StudentAccountException::statusChangeFailedDueToServerError('reactivated', $exception)->getMessage(),
            );
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
        } catch (CircuitBreakerException $exception) {
            // External account service is temporarily unavailable.
            return back()->with('flash_error', StudentAccountException::accountServiceUnavailable($exception)->getMessage());
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        } catch (QueryException $exception) {
            Log::error('Failed to delete student account.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->with(
                'flash_error',
                $connectionFailure?->getMessage()
                ?? StudentAccountException::deletionFailedDueToServerError($exception)->getMessage(),
            );
        } catch (Throwable $exception) {
            // Unexpected error (e.g. an unhandled provider failure).
            Log::error('Unexpected error while deleting student account.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('flash_error', StudentAccountException::deletionFailedDueToUnexpectedError($exception)->getMessage());
        }

        $this->audit->accountDeleted($request, $student);

        return back()->with('flash_success', 'Student account has been permanently deleted.');
    }

    public function createSupabaseAccount(Request $request, Student $student): RedirectResponse
    {
        try {
            $credentials = $this->service->registerStudent($student);
        } catch (CircuitBreakerException $exception) {
            // Supabase authentication service is temporarily unavailable.
            return back()->withErrors([
                'register' => StudentAccountException::accountServiceUnavailable($exception)->getMessage(),
            ]);
        } catch (StudentAccountException $exception) {
            // Business validation failed.
            return back()->withErrors([
                'register' => $exception->getMessage(),
            ]);
        } catch (QueryException $exception) {
            // Database persistence failed.
            Log::error('Failed to persist student account during registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            $connectionFailure = InfrastructureException::fromDatabaseError($exception);

            return back()->withErrors([
                'register' => $connectionFailure?->getMessage()
                    ?? StudentAccountException::registrationFailedDueToServerError($exception)->getMessage(),
            ]);
        } catch (Throwable $exception) {
            // Fallback for unhandled errors.
            Log::error('Unexpected error during student account registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'register' => StudentAccountException::registrationFailedDueToUnexpectedError($exception)->getMessage(),
            ]);
        }

        // Record the successful account creation.
        $this->audit->accountCreated($request, $student, $credentials);

        return back()
            ->with('flash_success', 'Supabase account created and student verified.')
            ->with('flash_student_credentials', [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);
    }
}