<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus;
use App\Enums\YearLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property int $id
 * @property string|null $user_id
 * @property string $student_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $anonymous_name
 * @property StudentStatus $status
 * @property int $year_level
 * @property string $section
 * @property string|null $daily_result
 *
 * @property-read string $name
 * @property-read string $verification_status
 *
 * @method static Builder|Student verified()
 * @method static Builder|Student withStatus(StudentStatus $status)
 * @method static Builder|Student matchingSearch(string $search)
 * @method static Builder|Student byYearLevel(int $yearLevel)
 * @method static Builder|Student byTab(string $tab)
 */

class Student extends Model
{
    protected $table = 'students';
    public $timestamps = false;
    public const int ADMIN_PAGE_SIZE = 7;

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'anonymous_name',
        'status',
        'year_level',
        'section',
        'daily_result',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'year_level' => 'integer',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(): string => str("{$this->first_name} {$this->last_name}")
                ->squish()
                ->toString(),
        );
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', StudentStatus::Verified->value);
    }

    public function scopeWithStatus(Builder $query, StudentStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        return $query->where(
            fn(Builder $q) => $q
                ->where('student_number', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
        );
    }

    public function scopeByYearLevel(Builder $query, int $yearLevel): Builder
    {
        return $query->where('year_level', $yearLevel);
    }

    public function scopeByTab(Builder $query, string $tab): Builder
    {
        $status = StudentStatus::tryFrom($tab);

        if ($status !== null) {
            return $query->withStatus($status);
        }

        return $query;
    }

    public static function queryWithFilters(array $filters): Builder
    {
        $search = str($filters['search'] ?? '')->squish()->toString();

        return static::query()
            ->when(
                $search !== '',
                fn(Builder $q) => $q->matchingSearch($search)
            )
            ->when(
                filled($filters['year_level'] ?? null),
                fn(Builder $q) => $q->byYearLevel($filters['year_level'])
            );
    }

    public static function queryWithFiltersAndTab(array $filters): Builder
    {
        return static::queryWithFilters($filters)->byTab($filters['tab'] ?? 'All');
    }

    protected static function filteredQuery(array $filters): Builder
    {
        return static::queryWithFiltersAndTab($filters)
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public static function countsByTab(array $filters): array
    {
        $base = static::queryWithFilters($filters);

        $countsPerStatus = (clone $base)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'All' => (clone $base)->count(),
            'Pending' => $countsPerStatus->get(StudentStatus::Pending->value, 0),
            'Verified' => $countsPerStatus->get(StudentStatus::Verified->value, 0),
            'Suspended' => $countsPerStatus->get(StudentStatus::Suspended->value, 0),
        ];
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        return static::filteredQuery($filters)
            ->paginate(static::ADMIN_PAGE_SIZE)
            ->withQueryString()
            ->through(fn(Student $student): array => [
                'id' => $student->id,
                'student_id' => $student->student_number,
                'name' => $student->name,
                'year_level' => YearLevel::tryFrom($student->year_level)?->label() ?? 'Not Set',
                'section' => $student->section,
                'verification_status' => $student->displayVerificationStatus(),
                'account_status' => $student->status->isActive() ? 'active' : 'suspended',
            ]);
    }

    private function displayVerificationStatus(): string
    {
        if ($this->status === StudentStatus::Suspended) {
            return StudentStatus::Verified->value;
        }

        return $this->status->value;
    }
}