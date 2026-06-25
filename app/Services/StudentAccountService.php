<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SupabaseAuthInterface;
use App\Enums\StudentStatus;
use App\Exceptions\CircuitBreakerException;
use App\Exceptions\StudentAccountException;
use App\Models\Notification;
use App\Models\Student;
use App\Mail\StudentCredentialsMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

use function count;
use function is_string;
use function str_contains;
use function strlen;

final class StudentAccountService
{
    public function __construct(
        private readonly SupabaseAuthInterface $supabase,
    ) {
    }

    /**
     * @throws StudentAccountException when the student is not Pending or Unverified.
     */
    public function verifyStudent(Student $student): void
    {
        $this->ensureStudentCanBeVerified($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * @throws StudentAccountException when the student is not in a rejectable state.
     */
    public function rejectStudent(Student $student): void
    {
        $this->ensureStudentCanBeRejected($student);

        $student->delete();
    }

    /**
     * @throws StudentAccountException when the student is not Verified.
     */
    public function suspendStudent(Student $student): void
    {
        $this->ensureStudentCanBeSuspended($student);

        $student->update(['status' => StudentStatus::Suspended->value]);
    }

    /**
     * @throws StudentAccountException when the student is not Suspended.
     */
    public function reactivateStudent(Student $student): void
    {
        $this->ensureStudentCanBeReactivated($student);

        $student->update(['status' => StudentStatus::Verified->value]);
    }

    /**
     * Permanently delete a student record and their Supabase auth account (if one exists).
     */
    public function deleteStudent(Student $student): void
    {
        if ($student->auth_user_id !== null) {
            try {
                $this->supabase->deleteAdminAccountApiCall($student->auth_user_id);
            } catch (Throwable $e) {
                Log::channel('auth')->error('Failed to delete Supabase auth account for student.', [
                    'student_id' => $student->id,
                    'auth_user_id' => $student->auth_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $student->delete();
    }

    /**
     * @return array{email: string, password: string, supabase_user_id: string}
     *
     * @throws StudentAccountException when a business rule is violated or the
     *         account provider rejects the request.
     * @throws CircuitBreakerException when the account provider is unavailable.
     */
    public function createSupabaseAccountForStudent(Student $student): array
    {
        // Ensure the student meets all requirements before account creation.
        $this->ensureStudentIsEligibleForRegistration($student);
        $this->ensureStudentNumberUsesRequiredLength($student);
        $this->ensureStudentHasValidPersonalEmail($student);

        $email = "{$student->student_number}@moodlink.com";
        $password = $this->generateInitialPassword();
        $supabaseUserId = $this->createSupabaseAuthAccountAndReturnUserId($student, $email, $password); // UUID from Supabase auth.users.

        // Automatically verify the student to allow mobile app access.
        $this->verifyStudent($student);

        // Email the crenditals to the student's personal email
        $this->sendInitialPasswordToPersonalEmail($student, $email, $password);

        // Prompt the student to change their initial password on first login.
        $this->notifyStudentToChangeInitialPassword($student);

        return [
            'email' => $email,
            'password' => $password,
            'supabase_user_id' => $supabaseUserId,
        ];
    }

    /**
     * Create the Supabase auth account and return the auth.users UUID,
     * translating provider failures into typed, user-safe domain exceptions
     * instead of leaking raw API errors.
     *
     * @throws StudentAccountException
     * @throws CircuitBreakerException
     */
    private function createSupabaseAuthAccountAndReturnUserId(Student $student, string $email, string $password): string
    {
        try {
            $response = $this->supabase->createStudentAccountApiCall(
                email: $email,
                password: $password,
                data: [
                    'student_id' => $student->id,
                    'name' => $student->name,
                ],
                emailConfirm: true,
            );
        } catch (CircuitBreakerException $e) {
            // Infrastructure-level failure: let the controller surface it.
            throw $e;
        } catch (Throwable $e) {
            Log::channel('auth')->error('Supabase student account provisioning failed.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            if (str_contains(strtolower($e->getMessage()), 'already been registered')) {
                throw StudentAccountException::loginEmailAlreadyRegistered($e);
            }

            throw StudentAccountException::accountCreationRejectedByAuthService($e);
        }

        // The Supabase admin API returns the new auth.users row, whose id is the
        $supabaseUserId = $response['id'] ?? null; // student's authentication UUID.

        if (!is_string($supabaseUserId) || $supabaseUserId === '') {
            Log::channel('auth')->error('Supabase account created but no auth.users id was returned.', [
                'student_id' => $student->id,
            ]);

            throw StudentAccountException::accountCreationRejectedByAuthService();
        }

        return $supabaseUserId;
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function ensureStudentCanBeVerified(Student $student): void
    {
        $isEligible = $student->status === StudentStatus::Pending
            || $student->status === StudentStatus::Unverified;

        if (!$isEligible) {
            throw StudentAccountException::studentMustBePendingOrUnverifiedToBeVerified();
        }
    }


    private function ensureStudentCanBeSuspended(Student $student): void
    {
        if ($student->status !== StudentStatus::Verified) {
            throw StudentAccountException::studentMustBeVerifiedToBeSuspended();
        }
    }

    private function ensureStudentCanBeReactivated(Student $student): void
    {
        if ($student->status !== StudentStatus::Suspended) {
            throw StudentAccountException::studentMustBeSuspendedToBeReactivated();
        }
    }

    private function ensureStudentIsEligibleForRegistration(Student $student): void
    {
        if ($student->status !== StudentStatus::Pending) {
            throw StudentAccountException::studentMustBePendingToRegisterAccount();
        }
    }

    private function ensureStudentNumberUsesRequiredLength(Student $student): void
    {
        $studentNumber = trim((string) $student->student_number);

        if (preg_match('/^\d{9}$/', $studentNumber) !== 1) {
            throw StudentAccountException::studentIdentifierMustBeNineDigits();
        }
    }
    private function ensureStudentHasValidPersonalEmail(Student $student): void
    {
        $email = trim((string) $student->personal_email);

        if ($email === '') {
            throw StudentAccountException::personalEmailRequiredBeforeAccountCreation();
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw StudentAccountException::personalEmailFormatIsInvalid($email);
        }
    }

    private function ensureStudentCanBeRejected(Student $student): void
    {
        if ($student->user_id !== null) {
            throw StudentAccountException::cannotRejectStudentWithActiveAccount();
        }

        if ($student->status !== StudentStatus::Pending) {
            throw StudentAccountException::studentMustBePendingToBeRejected();
        }
    }

    private function sendInitialPasswordToPersonalEmail(Student $student, string $email, string $password): void
    {
        if (!$student->personal_email) {
            Log::channel('mail')->warning('Unable to send initial password email: student has no personal email address', [
                'student_id' => $student->id,
            ]);

            return;
        }

        try {
            Mail::to($student->personal_email)
                ->send(new StudentCredentialsMail($student, $email, $password));

            Log::channel('mail')->info('Student initial password notification successfully processed and delivered', [
                'student_id' => $student->id,
                'recipient_email' => $student->personal_email,
                'mail_event' => 'initial_password_delivery_success',
            ]);

        } catch (Throwable $e) {
            // Don't fail account creation if email delivery fails
            Log::channel('mail')->warning('Failed to email student crendetials', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyStudentToChangeInitialPassword(Student $student): void
    {
        try {
            Notification::create([
                'student_id' => $student->id,
                'title' => 'Initial Password Change Required',
                'content' => 'Your account is currently using an initial password. For your account security, please change your password to a secure, unique one that only you know in order to protect your account. Changing your original password lowers the possibility of account compromise, credential exposure, and unauthorized access.',
                'type' => 'security_alert',
                'is_seen' => DB::raw('false'),
                'datetime' => now(),
            ]);
        } catch (Throwable $e) {
            // Don't fail account creation if the notification cannot be stored.
            Log::warning('Failed to create initial password notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateInitialPassword(int $length = 8): string
    {
        // random 8 character one lowercase letter, maximum two uppercase letters, and one symbol.
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $symbols = '!@$^-';

        $all = $lower;

        $chars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
        ];

        // Add remaining characters 
        while (count($chars) < $length - 1) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle all variables
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];

        return implode('', $chars) . $symbol;
    }
}