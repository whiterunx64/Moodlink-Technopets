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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
 * @property string|null $personal_email
 * @property string|null $contact_number
 *
 * @property-read string|null $auth_user_id
 * @property-read string $name
 * @property-read string $studentNameInitials
 * @property-read string $year_level_label
 * @property-read string $account_status
 * @property-read string $verification_status
 * @property-read Collection<int, \App\Models\Appointment> $appointments
 *
 * @method static Builder|Student whereStatusIsVerified()
 * @method static Builder|Student whereStatus(\App\Enums\StudentStatus $status)
 * @method static Builder|Student matchingSearch(string $search)
 * @method static Builder|Student byYearLevel(int $yearLevel)
 * @method static Builder|Student byTab(string $tab)
 */

class Student extends Model
{
    use HasFilters;

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
        'personal_email',
        'contact_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'year_level' => 'integer',
        ];
    }

    /**
     * Resolve the student from a Supabase auth.users UUID via the auth user's
     * email ("{student_number}@moodlink.com"). Lets routes be addressed by the
     * non-enumerable auth UUID (IDOR-safe) instead of the integer id.
     */
    public static function findBySupabaseAuthId(string $authUserId): ?self
    {
        $email = DB::table('auth.users')
            ->where('id', $authUserId)
            ->value('email');

        if ($email === null) {
            return null;
        }

        return static::query()
            ->where('student_number', Str::before((string) $email, '@'))
            ->first();
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
            if ($this->status === StudentStatus::Verified) {
                return 'active';
            }

            return 'suspended';
        });
    }

    protected function verificationStatus(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->status === StudentStatus::Suspended) {
                return StudentStatus::Verified->value;
            }

            return $this->status->value;
        });
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
            ->whereStatusIsVerified()
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

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    public static function statusCountsForFilters(array $filters): \Illuminate\Support\Collection
    {
        $base = static::queryFilteredBySearchAndYearLevel($filters);

        return (clone $base)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->put('All', (clone $base)->count());
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        // Correlated subquery: the Supabase auth.users UUID for this student,
        // matched on the deterministic login email (null while still pending).
        $authUserId = DB::table('auth.users')
            ->select('id')
            ->whereRaw("email = students.student_number || '@moodlink.com'")
            ->limit(1);

        return static::queryFilteredBySearchAndYearLevel($filters)
            ->byTab($filters['tab'] ?? 'All')
            ->select('students.*')
            ->selectSub($authUserId, 'auth_user_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(9)
            ->withQueryString();
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