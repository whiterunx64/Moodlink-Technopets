<?php

namespace App\Services;

use App\Contracts\SupabaseAuthInterface;
use App\Enums\StudentStatus;
use App\Enums\YearLevel;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Encapsulates student-account queries and status transitions for the
 * User Accounts admin module. All filtering/pagination happens in the
 * database so the page scales to large student populations.
 */
final class StudentAccountService
{
    public const PER_PAGE = 7;

    /**
     * A page of students shaped for the User Accounts table.
     *
     * @param  array{search?: ?string, year_level?: ?int, tab?: ?string}  $filters
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return $this->applyFilters(Student::query(), $filters)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Student $student) => $this->toRow($student));
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
        $base = $this->applyFilters(Student::query(), $filters, withTab: false);

        $byStatus = (clone $base)
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->pluck('aggregate', 'status');

        return [
            'All'       => (int) (clone $base)->count(),
            'Pending'   => (int) ($byStatus[StudentStatus::Pending->value] ?? 0),
            'Verified'  => (int) ($byStatus[StudentStatus::Verified->value] ?? 0),
            'Suspended' => (int) ($byStatus[StudentStatus::Suspended->value] ?? 0),
        ];
    }

    /**
     * Apply a status transition, guarding against illegal moves.
     *
     * @throws \DomainException when the transition is not allowed.
     */
    public function changeStatus(Student $student, StudentStatus $target): Student
    {
        if (! $student->status->canTransitionTo($target)) {
            throw new \DomainException(
                "Cannot change status from {$student->status->value} to {$target->value}."
            );
        }

        $student->update(['status' => $target]);

        return $student;
    }

    /**
     * Shared filter pipeline for the list and the counts.
     *
     * @param  Builder<Student>  $query
     * @param  array{search?: ?string, year_level?: ?int, tab?: ?string}  $filters
     * @return Builder<Student>
     */
    private function applyFilters(Builder $query, array $filters, bool $withTab = true): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $like = "%{$search}%";
                $q->where('student_number', 'ilike', $like)
                    ->orWhere('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like);
            });
        }

        if (! empty($filters['year_level'])) {
            $query->where('year_level', (int) $filters['year_level']);
        }

        if ($withTab) {
            $this->applyTab($query, $filters['tab'] ?? 'All');
        }

        return $query;
    }

    /**
     * Map a frontend tab to a database status filter.
     *
     * @param  Builder<Student>  $query
     */
    private function applyTab(Builder $query, ?string $tab): void
    {
        $status = match ($tab) {
            'Pending'   => StudentStatus::Pending,
            'Verified'  => StudentStatus::Verified,
            'Suspended' => StudentStatus::Suspended,
            default     => null, // 'All'
        };

        if ($status !== null) {
            $query->withStatus($status);
        }
    }

    /**
     * Serialise a single student for the frontend.
     *
     * @return array<string, mixed>
     */
    private function toRow(Student $student): array
    {
        return [
            'id'                  => $student->id,
            'student_id'          => $student->student_number,
            'name'                => $student->name,
            'year_level'          => YearLevel::tryFrom($student->year_level)?->label() ?? 'Not Set',
            'section'             => $student->section,
            'verification_status' => $this->verificationStatus($student->status),
            'account_status'      => $student->status->isActive() ? 'active' : 'suspended',
        ];
    }

    /**
     * Map the single DB status onto the frontend's verification value.
     * A suspended account keeps its underlying "verified" verification badge.
     */
    private function verificationStatus(StudentStatus $status): string
    {
        return match ($status) {
            StudentStatus::Suspended => StudentStatus::Verified->value,
            default                  => $status->value,
        };
    }
}
