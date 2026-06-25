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

/**
 * @property int $id
 * @property string|null $user_id
 * @property string $student_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $anonymous_name
 * @property StudentStatus $status
 * @property int $year_level
 * @property string $program
 * @property string|null $daily_result
 * @property Carbon|null $risk_start_date
 * @property string|null $personal_email
 * @property string|null $contact_number
 *
 * @property-read string|null $auth_user_id
 * @property-read string $name
 * @property-read string $studentNameInitials
 * @property-read int $days_at_risk
 * @property-read string $year_level_label
 * @property-read Collection<int, \App\Models\Appointment> $appointments
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
        'user_id',
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

    /**
     * Resolve the student from a Supabase auth.users UUID via the auth user's
     * email ("{student_number}@moodlink.com"). Lets routes be addressed by the
     * non-enumerable auth UUID (IDOR-safe) instead of the integer id.
     */
    public static function findBySupabaseAuthId(string $authUserId): ?self
    {
        return static::query()
            ->whereExists(function ($query) use ($authUserId) {
                $query->selectRaw('1')
                    ->from('auth.users')
                    ->where('id', $authUserId)
                    ->whereRaw("email = students.student_number || '@moodlink.com'");
            })
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
            return match ($this->status) {
                StudentStatus::Verified => 'active',
                StudentStatus::Suspended => 'suspended',
                StudentStatus::Pending => 'pending',
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
     * Whole days since the student was flagged At Risk (1 on the first day),
     * or 0 when not flagged. Drives the escalation label.
     */
    protected function daysAtRisk(): Attribute
    {
        return Attribute::make(get: function (): int {
            if ($this->risk_start_date === null) {
                return 0;
            }

            return (int) $this->risk_start_date->copy()->startOfDay()
                ->diffInDays(Carbon::now()->startOfDay()) + 1;
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

    public function scopeByYearLevel(Builder $query, int $yearLevel): Builder
    {
        return $query->where('year_level', $yearLevel);
    }

    public function scopeByTab(Builder $query, string $tab): Builder
    {
        if ($tab === '' || $tab === 'all') {
            return $query;
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
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS verified,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS suspended',
                [
                    StudentStatus::Pending->value,
                    StudentStatus::Verified->value,
                    StudentStatus::Suspended->value,
                ],
            )
            ->first();
    }

    public static function paginatedListWithFilters(array $filters): LengthAwarePaginator
    {
        $authUserId = DB::table('auth.users')
            ->select('id')
            ->whereRaw("email = students.student_number || '@moodlink.com'")
            ->limit(1);

        return static::query()
            ->filter($filters)
            ->byTab($filters['tab'] ?? 'All')
            ->select('students.*')
            ->selectSub($authUserId, 'auth_user_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(8)
            ->withQueryString();
    }

    public function scopeFlaggedAtRisk(Builder $query): Builder
    {
        return $query
            ->whereStatusIsVerified()
            ->whereNotNull('risk_start_date')
            ->whereDoesntHave('appointments', fn(Builder $q) => $q->openConsultation());
    }

    public static function getAtRiskCount(): int
    {
        return static::query()->flaggedAtRisk()->count();
    }

    public static function atRiskList(): Collection
    {
        return static::query()
            ->flaggedAtRisk()
            ->orderBy('risk_start_date')
            ->get();
    }

}