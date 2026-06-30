<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function accountAccepted(Request $request, Student $student): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.accept_registration',
            description: 'Administrator accepted student registration.',
            context: [
                'category' => 'student_management',
                'status' => 'verified',
            ],
        );
    }

    public function registrationRejected(Request $request, Student $student): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.rejected',
            description: 'Administrator rejected student registration.',
            context: [
                'category' => 'student_management',
                'status' => 'rejected',
            ],
        );
    }

    public function accessRestricted(Request $request, Student $student): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.access_restricted',
            description: 'Administrator suspended a student account and restricted access.',
            context: [
                'category' => 'student_management',
                'status' => 'suspended',
            ],
        );
    }

    public function accessRestored(Request $request, Student $student): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.access_restored',
            description: 'Administrator reactivated a student account and restored access.',
            context: [
                'category' => 'student_management',
                'status' => 'verified',
            ],
        );
    }

    public function accountDeleted(Request $request, Student $student): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.deleted',
            description: 'Administrator permanently deleted a student account.',
            context: [
                'category' => 'student_management',
                'status' => 'deleted',
            ],
        );
    }

    /**
     * @param array{email: string, password: string, supabase_user_id: string} $credentials
     */
    public function accountCreated(Request $request, Student $student, array $credentials): void
    {
        $this->record(
            $request,
            $student,
            event: 'student_account.created',
            description: 'Administrator created a new student authentication account.',
            context: [
                'purpose' => 'Student account provisioning',
                'provider' => 'supabase',
                'login_email' => $credentials['email'],
            ],
            // UUID of the student's record in Supabase auth.users.
            extraTarget: ['supabase_user_id' => $credentials['supabase_user_id']],
        );
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extraTarget
     */
    private function record(
        Request $request,
        Student $student,
        string $event,
        string $description,
        array $context,
        array $extraTarget = [],
    ): void {
        $admin = $request->user();

        Log::channel('audit')->info($event, [
            'event' => $event,
            'who' => [
                'actor_type' => 'admin',
                'actor_id' => $admin?->id,
                'actor_email' => $admin?->email,
            ],
            'what' => [
                'description' => $description,
                'target_type' => 'student',
                'target_id' => $student->id,
                'student_number' => $student->student_number,
                'student_name' => $student->name,
                ...$extraTarget,
            ],
            'where' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'context' => $context,
            'when' => now()->toIso8601String(),
        ]);
    }
}