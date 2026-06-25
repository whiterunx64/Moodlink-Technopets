<?php

namespace App\Http\Controllers;

use App\Exceptions\CircuitBreakerException;
use App\Exceptions\StudentAccountException;
use App\Enums\StudentStatus;
use App\Http\Requests\UserAccountFilterRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
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
        private readonly StudentAccountService $service,
    ) {
    }

    public function index(UserAccountFilterRequest $request): Response
    {
        $studentFilters = $request->filters();
        $students = Student::paginatedListWithFilters($studentFilters)
            ->through(fn(Student $student): array => [
                'id' => $student->id,
                'auth_user_id' => $student->auth_user_id,
                'student_id' => $student->student_number,
                'name' => $student->name,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'personal_email' => $student->personal_email,
                'contact_number' => $student->contact_number,
                'year_level' => $student->year_level_label,
                'program' => $student->program,
                'verification_status' => $student->verification_status,
                'account_status' => $student->account_status,
            ]);
        $statusCounts = Student::tabCounts($studentFilters);

        $tabCounts = [
            'all' => $statusCounts->total,
            'pending' => $statusCounts->pending,
            'verified' => $statusCounts->verified,
            'suspended' => $statusCounts->suspended,
        ];

        return Inertia::render('UserAccounts/Index', [
            'students' => $students,
            'tabCounts' => $tabCounts,
            'filters' => $studentFilters,
        ]);
    }

    /**
     * Accept a pending/unverified student's registration by marking them
     * verified, without provisioning an authentication account.
     */
    public function acceptRegistration(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->verifyStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        // Security audit log for GCU admin actions
        $admin = $request->user();
        Log::channel('audit')->info('student_account.accept_registration', [
            'event' => 'student_account.accept_registration',
            'who' => [
                'actor_type' => 'admin',
                'actor_id' => $admin?->id,
                'actor_email' => $admin?->email,
            ],
            'what' => [
                'description' => 'Administrator accepted student registration.',
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
                'status' => 'verified',
            ],
            'when' => now()->toIso8601String(),
        ]);

        return back()->with(
            'flash_success',
            'Student registration has been accepted and marked as verified.'
        );
    }

    public function destroyRegistration(Request $request, Student $student): RedirectResponse
    {
        try {
            $this->service->rejectStudent($student);
        } catch (StudentAccountException $exception) {
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
     * Restrict a verified student's access by suspending their account.
     *
     * Bound by the Supabase auth.users UUID (not the integer id) to avoid IDOR.
     */
    public function restrictAccess(string $studentUuid): RedirectResponse
    {
        try {
            $student = Student::findBySupabaseAuthId($studentUuid)
                ?? throw StudentAccountException::authAccountNotFound();

            $this->service->suspendStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student account has been suspended and access has been restricted.'
        );
    }

    /**
     * Restore a suspended student's access by reactivating their account.
     *
     * Bound by the Supabase auth.users UUID (not the integer id) to avoid IDOR.
     */
    public function restoreAccess(string $studentUuid): RedirectResponse
    {
        try {
            $student = Student::findBySupabaseAuthId($studentUuid)
                ?? throw StudentAccountException::authAccountNotFound();

            $this->service->reactivateStudent($student);
        } catch (StudentAccountException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Student account has been reactivated and restored to active status.'
        );
    }

    /**
     * Permanently delete a student record and their Supabase auth account.
     */
    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->service->deleteStudent($student);

        $admin = $request->user();
        Log::channel('audit')->info('student_account.deleted', [
            'event' => 'student_account.deleted',
            'who' => [
                'actor_type' => 'admin',
                'actor_id' => $admin?->id,
                'actor_email' => $admin?->email,
            ],
            'what' => [
                'description' => 'Administrator permanently deleted a student account.',
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
                'status' => 'deleted',
            ],
            'when' => now()->toIso8601String(),
        ]);

        return back()->with('flash_success', 'Student account has been permanently deleted.');
    }

    /**
     * Create a Supabase authentication account for a student's registration
     * (also marks the student verified).
     *
     * @throws StudentAccountException
     * @throws CircuitBreakerException
     */
    public function storeRegistration(Request $request, Student $student): RedirectResponse
    {
        try {
            $credentials = $this->service->createSupabaseAccountForStudent($student);
        } catch (CircuitBreakerException $exception) {
            return back()->withErrors([
                'register' => 'The account service is temporarily unavailable because of repeated connection problems. Please wait about a minute and try again. If the issue persists, contact your system administrator.',
            ]);
        } catch (StudentAccountException $exception) {
            // Business-rule violation or a provider rejection — the message is curated and safe to show.
            return back()->withErrors(['register' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            Log::error('Failed to persist student account during registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'register' => 'The account could not be saved because of a server error. The account may have been partially created — please contact your system administrator before retrying.',
            ]);
        } catch (Throwable $exception) {
            // Never leak raw internal errors to the admin; log and show a safe message.
            Log::error('Unexpected error during student account registration.', [
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'register' => 'An unexpected error occurred while creating the account. Please try again, or contact your system administrator if the problem persists.',
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
                // UUID of the student's record in Supabase auth.users.
                'supabase_user_id' => $credentials['supabase_user_id'],
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
            ->with('flash_student_credentials', [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);
    }
}