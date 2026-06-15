<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Queries\BuildStudentAccountQuery;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Encapsulates student-account orchestration and status transitions
 * for the User Accounts admin module.
 */
final class StudentAccountService
{
    public const int PER_PAGE = 7;

    /**
     * A page of students shaped for the User Accounts table.
     *
     * @param  array{search?: ?string, year_level?: ?int, tab?: ?string}  $filters
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return BuildStudentAccountQuery::apply(Student::query(), $filters)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Student $student) => $student->toAdminRow());
    }

    /**
     * Per-tab counts (independent of the active tab), respecting search/year
     * filters. One grouped query rather than four round-trips.
     *
     * @param  array{search?: ?string, year_level?: ?int}  $filters
     * @return array<string, int>
     */
    public function tabCounts(array $filters): array
    {
        $base = BuildStudentAccountQuery::apply(Student::query(), $filters, withTab: false);

        $byStatus = (clone $base)
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->pluck('aggregate', 'status');

        return [
            'All'       => (clone $base)->count(),
            'Pending'   => (int) ($byStatus[StudentStatus::Pending->value] ?? 0),
            'Verified'  => (int) ($byStatus[StudentStatus::Verified->value] ?? 0),
            'Suspended' => (int) ($byStatus[StudentStatus::Suspended->value] ?? 0),
        ];
    }

    /**
     * Apply a status transition, guarding against illegal moves.
     *
     * @throws DomainException when the transition is not allowed.
     */
    public function changeStatus(Student $student, StudentStatus $target): Student
    {
        if (! $student->status->canTransitionTo($target)) {
            throw new DomainException(
                "Cannot change status from {$student->status->value} to {$target->value}."
            );
        }

        $student->update(['status' => $target]);

        return $student;
    }
}