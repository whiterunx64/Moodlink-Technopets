<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus;
use App\Enums\YearLevel;
use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property int $id
 * @property string|null $user_id
 * @property string|null $uuid
 * @property string $student_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $anonymous_name
 * @property StudentStatus $status
 * @property int $year_level
 * @property string $program
 * @property string|null $daily_result
 * @property string|null $personal_email
 * @property string|null $contact_number
 * @property \Illuminate\Support\Carbon|null $risk_start_date
 *
 * @property-read string $name
 * @property-read string $studentNameInitials
 * @property-read string $year_level_label
 * @property-read string $account_status
 * @property-read string $verification_status
 * @property-read Collection<int, \App\Models\Appointment> $appointments
 * @property-read int|null $appointments_count
 *
 * @method static Builder|Student whereStatusIsVerified()
 * @method static Builder|Student matchingSearch(string $search)
 * @method static Builder|Student byYearLevel(int $yearLevel)
 * @method static Builder|Student byTab(string $tab)
 * @method static Builder|Student flaggedAtRisk()
 */

class Student extends Model
{
    use HasFilters;

    protected $table = 'students';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'student_number',
        'first_name',
        'last_name',
        'anonymous_name',
        'status',
        'year_level',
        'program',
        'daily_result',
        'risk_start_date',
        'personal_email',
        'contact_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'year_level' => 'integer',
            'risk_start_date' => 'date',
        ];
    }

    public static function findBySupabaseAuthId(string $authUserId): ?self
    {
        return static::query()
            ->where('uuid', $authUserId)
            ->first();
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $query = static::query();

        if (ctype_digit((string) $value)) {
            $query->where($this->getKeyName(), $value);
        } else {
            $query->where('uuid', $value);
        }

        return $query->first();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(): string => str("{$this->first_name} {$this->last_name}")
                ->squish()
                ->toString(),
        );
    }

    protected function studentNameInitials(): Attribute
    {
        return Attribute::make(
            get: fn(): string => collect(explode(' ', trim($this->name)))
                ->filter()
                ->map(fn(string $word): string => strtoupper($word[0] ?? ''))
                ->take(2)
                ->implode(''),
        );
    }

    protected function yearLevelLabel(): Attribute
    {
        return Attribute::make(get: function (): string {
            $label = YearLevel::tryFrom($this->year_level)?->toOrdinal();

            if ($label !== null) {
                return $label;
            }

            return 'Not Set';
        });
    }

    protected function accountStatus(): Attribute
    {
        return Attribute::make(get: function (): string {
            return match ($this->status) {
                StudentStatus::Verified => 'active',
                StudentStatus::Suspended => 'suspended',
                StudentStatus::Pending, StudentStatus::Unverified => 'pending',
            };
        });
    }

    protected function verificationStatus(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->status->value,
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

    public function scopeWhereStatusIsVerified(Builder $query): Builder
    {
        return $query->where('status', StudentStatus::Verified->value);
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

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if ($search = str($filters['search'] ?? '')->squish()->toString()) {
            $query->matchingSearch($search);
        }

        if (filled($filters['year_level'] ?? null)) {
            $query->byYearLevel($filters['year_level']);
        }

        return $query;
    }

    public function scopeFlaggedAtRisk(Builder $query): Builder
    {
        return $query
            ->whereStatusIsVerified()
            ->whereNotNull('risk_start_date');
    }

    public function scopeByYearLevel(Builder $query, int $yearLevel): Builder
    {
        return $query->where('year_level', $yearLevel);
    }

    public function scopeByTab(Builder $query, string $tab): Builder
    {
        if ($tab === '' || $tab === 'all') {
            return $query;
        }

        if ($tab === StudentStatus::Pending->value) {
            return $query->whereIn('status', [
                StudentStatus::Pending->value,
                StudentStatus::Unverified->value,
            ]);
        }

        $status = StudentStatus::tryFrom($tab);

        if ($status === null) {
            return $query;
        }

        return $query->where('status', $status->value);
    }

    public static function tabCounts(array $filters): object
    {
        return static::query()
            ->filter($filters)
            ->selectRaw(
                'COUNT(*) AS total,
             SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS pending,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS verified,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS suspended',
                [
                    StudentStatus::Pending->value,
                    StudentStatus::Unverified->value,
                    StudentStatus::Verified->value,
                    StudentStatus::Suspended->value,
                ],
            )
            ->first();
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        return static::query()
            ->filter($filters)
            ->byTab($filters['tab'] ?? 'All')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Student>
     */
    public static function getVerifiedStudents(): Collection
    {
        return static::query()
            ->whereStatusIsVerified()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return Collection<int, Student>
     */
    public static function verifiedListByProgram(string $program): Collection
    {
        return static::query()
            ->whereStatusIsVerified()
            ->where('program', $program)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public static function flaggedAtRiskCount(): int
    {
        return static::query()
            ->flaggedAtRisk()
            ->count();
    }

    public static function atRiskCountForProgram(string $program): int
    {
        return static::query()
            ->flaggedAtRisk()
            ->where('program', $program)
            ->count();
    }

    /**
     * @return Collection<int, Student>
     */
    public static function flaggedAtRiskList(): Collection
    {
        return static::query()
            ->flaggedAtRisk()
            ->orderBy('risk_start_date')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>  program => at-risk student count
     */
    public static function atRiskCountsGroupedByProgram(): \Illuminate\Support\Collection
    {
        return static::query()
            ->flaggedAtRisk()
            ->whereNotNull('program')
            ->selectRaw('program, count(*) as at_risk_count')
            ->groupBy('program')
            ->withCasts(['at_risk_count' => 'integer'])
            ->pluck('at_risk_count', 'program');
    }

    /**
     * @return list<string>
     */
    public static function verifiedPrograms(): array
    {
        return static::query()
            ->whereStatusIsVerified()
            ->whereNotNull('program')
            ->distinct()
            ->orderBy('program')
            ->pluck('program')
            ->all();
    }

}