<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus;
use App\Traits\HasFilters;
use App\Traits\HasStudentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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
 * @property-read Collection<int, \App\Models\Appointment> $appointments
 *
 * @method static Builder|Student verified()
 * @method static Builder|Student whereStatus(\App\Enums\StudentStatus $status)
 * @method static Builder|Student matchingSearch(string $search)
 * @method static Builder|Student byYearLevel(int $yearLevel)
 * @method static Builder|Student byTab(string $tab)
 *
 * @mixin HasStudentStatus
 */

class Student extends Model
{
    use HasFilters;
    use HasStudentStatus;

    protected $table = 'students';
    public $timestamps = false;

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

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'student_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'student_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', StudentStatus::Verified->value);
    }

    public function scopeWhereStatus(Builder $query, StudentStatus $status): Builder
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
        if ($tab === 'All' || $tab === '') {
            return $query;
        }

        $status = StudentStatus::tryFrom(strtolower($tab));

        if ($status !== null) {
            return $query->whereStatus($status);
        } else {
            return $query;
        }
    }
    public function scopeAtRisk(Builder $query, ?Carbon $from): Builder
    {
        $atRiskPosts = fn(Builder $query) => $query
            ->stressedOrDrained()
            ->startingFrom($from);

        return $query
            ->verified()
            ->whereHas('posts', $atRiskPosts, '>=', 1);
    }

    protected static function queryFilteredBySearchAndYearLevel(array $filters): Builder
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

    public static function countsByTab(array $filters): array
    {
        $base = static::queryFilteredBySearchAndYearLevel($filters);

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
        $query = static::queryFilteredBySearchAndYearLevel($filters)
            ->byTab($filters['tab'] ?? 'All')
            ->orderBy('last_name')
            ->orderBy('first_name');

        return $query
            ->paginate(7)
            ->withQueryString()
            ->through(fn(Student $student): array => [
                'id' => $student->id,
                'student_id' => $student->student_number,
                'name' => $student->name,
                'year_level' => $student->displayYearLevel(),
                'section' => $student->section,
                'verification_status' => $student->displayVerificationStatus(),
                'account_status' => $student->displayAccountStatus(),
            ]);
    }

    public static function getAtRiskCount(string $period): int
    {
        return static::query()
            ->atRisk(static::summaryReportPeriodStart($period)) // Apply period-based risk filter.
            ->count();
    }

    public static function atRiskList(string $period): Collection
    {
        $from = static::summaryReportPeriodStart($period);

        $concerningPosts = fn(Builder $query) => $query
            ->stressedOrDrained()
            ->startingFrom($from); // Limit posts by date.

        return static::query()
            ->atRisk($from) // Get students matching risk rules.
            ->withCount([
                'posts as concerning_count' => $concerningPosts, // Avoid loading full posts.
            ])
            ->orderByDesc('concerning_count')
            ->get();
    }

}