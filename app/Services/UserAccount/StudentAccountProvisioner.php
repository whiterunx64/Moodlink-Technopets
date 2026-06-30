<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Contracts\SupabaseAuthInterface;
use App\Exceptions\CircuitBreakerException;
use App\Exceptions\StudentAccountException;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Throwable;

use function is_string;
use function str_contains;

class StudentAccountProvisioner
{
    public function __construct(
        private readonly SupabaseAuthInterface $supabase,
    ) {
    }

    /**
     * @throws StudentAccountException When account creation fails or the email is already registered.
     * @throws CircuitBreakerException When the authentication service is unavailable.
     */
    public function createSupabaseAccount(Student $student, string $email, string $password): string
    {
        try {
            $response = $this->supabase->createAuthUser(
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
            throw $this->toAccountCreationException($student, $e);
        }

        return $this->extractSupabaseUserId($student, $response);
    }

    /**
     * @throws StudentAccountException When account deletion fails.
     */
    public function deleteSupabaseAccount(Student $student): void
    {
        if ($student->uuid === null) {
            return;
        }

        try {
            $this->supabase->deleteAuthUser($student->uuid);
        } catch (Throwable $e) {
            Log::channel('auth')->error('Failed to delete Supabase auth account for student.', [
                'student_id' => $student->id,
                'auth_user_id' => $student->uuid,
                'error' => $e->getMessage(),
            ]);

            throw StudentAccountException::studentAccountDeletionFailed($e);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Private Methods
    // ─────────────────────────────────────────────────────────────

    private function toAccountCreationException(Student $student, Throwable $e): StudentAccountException
    {
        Log::channel('auth')->error('Supabase student account provisioning failed.', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
        ]);

        if (str_contains(strtolower($e->getMessage()), 'already been registered')) {
            return StudentAccountException::studentAccountAlreadyExists($e);
        }

        return StudentAccountException::studentAccountCreationFailed($e);
    }

    private function extractSupabaseUserId(Student $student, array $response): string
    {
        $supabaseUserId = $response['id'] ?? null; // student's authentication UUID.

        if (!is_string($supabaseUserId) || $supabaseUserId === '') {
            Log::channel('auth')->error('Supabase account created but no auth.users id was returned.', [
                'student_id' => $student->id,
            ]);

            throw StudentAccountException::malformedProviderResponse();
        }

        return $supabaseUserId;
    }
}