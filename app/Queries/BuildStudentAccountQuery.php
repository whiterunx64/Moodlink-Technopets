<?php

namespace App\Queries;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

final class BuildStudentAccountQuery
{
  /**
   * @param  Builder<Student>  $query
   * @param  array{search?: ?string, year_level?: ?int, tab?: ?string}  $filters
   * @return Builder<Student>
   */
  public static function apply(Builder $query, array $filters, bool $withTab = true): Builder
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

    if (!empty($filters['year_level'])) {
      $query->where('year_level', (int) $filters['year_level']);
    }

    if ($withTab) {
      self::applyTab($query, $filters['tab'] ?? 'All');
    }

    return $query;
  }

  /** @param  Builder<Student>  $query */
  private static function applyTab(Builder $query, ?string $tab): void
  {
    $status = match ($tab) {
      'Pending' => StudentStatus::Pending,
      'Verified' => StudentStatus::Verified,
      'Suspended' => StudentStatus::Suspended,
      default => null,
    };

    if ($status !== null) {
      $query->withStatus($status);
    }
  }
}