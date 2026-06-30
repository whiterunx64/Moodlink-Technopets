<?php

declare(strict_types=1);

namespace App\Services\UserAccount;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QueryService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginatedStudentList(array $filters): LengthAwarePaginator
    {
        return Student::paginatedListWithFilters($filters)
            ->through(fn(Student $student): array => [
                'id' => $student->id,
                'auth_user_id' => $student->uuid,
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
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function tabCounts(array $filters): array
    {
        $statusCounts = Student::tabCounts($filters);

        return [
            'all' => $statusCounts->total,
            'pending' => $statusCounts->pending,
            'verified' => $statusCounts->verified,
            'suspended' => $statusCounts->suspended,
        ];
    }
}