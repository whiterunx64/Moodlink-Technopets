<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Enums\YearLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function intval;

/**
 * Student model representing enrolled users in the system.
 *
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
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 *
 * @method static Builder verified()
 * @method static Builder withStatus(StudentStatus $status)
 * @method static Builder matchingSearch(string $search)
 * @method static Builder byYearLevel(int $yearLevel)
 * @method static Builder byTab(string $tab)
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

    protected $casts = [
        'status' => StudentStatus::class,
        'year_level' => 'integer',
    ];

    /** @return HasMany<Post> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(): string => trim("{$this->first_name} {$this->last_name}"),
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
        $pattern = "%{$search}%";

        return $query->where(function (Builder $q) use ($pattern): void {
            $q->where('student_number', 'ilike', $pattern)
                ->orWhere('first_name', 'ilike', $pattern)
                ->orWhere('last_name', 'ilike', $pattern);
        });
    }

    public function scopeByYearLevel(Builder $query, int $yearLevel): Builder
    {
        return $query->where('year_level', $yearLevel);
    }

    public function scopeByTab(Builder $query, string $tab): Builder
    {
        $status = match ($tab) {
            'Pending' => StudentStatus::Pending,
            'Verified' => StudentStatus::Verified,
            'Suspended' => StudentStatus::Suspended,
            default => null,
        };

        return $status !== null ? $query->withStatus($status) : $query;
    }

    public static function queryWithFilters(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $yearLevel = (int) ($filters['year_level'] ?? 0);

        return static::query()
            ->when($search !== '', fn(Builder $q) => $q->matchingSearch($search))
            ->when($yearLevel !== 0, fn(Builder $q) => $q->byYearLevel($yearLevel));
    }

    public static function queryWithFiltersAndTab(array $filters): Builder
    {
        $tab = $filters['tab'] ?? 'All';

        return static::queryWithFilters($filters)->byTab($tab);
    }

    public static function countsByTab(array $filters): array
    {
        $base = static::queryWithFilters($filters);

        $countsPerStatus = (clone $base)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'All'       => (clone $base)->count(),
            'Pending'   => intval($countsPerStatus->get(StudentStatus::Pending->value,   0)),
            'Verified'  => intval($countsPerStatus->get(StudentStatus::Verified->value,  0)),
            'Suspended' => intval($countsPerStatus->get(StudentStatus::Suspended->value, 0)),
        ];
    }

    public function toListRow(): array
    {
        $yearLabel = YearLevel::tryFrom($this->year_level)?->label() ?? 'Not Set';
        $verificationStatus = $this->displayVerificationStatus($this->status);
        $accountStatus = $this->status->isActive() ? 'active' : 'suspended';

        return [
            'id' => $this->id,
            'student_id' => $this->student_number,
            'name' => $this->name,
            'year_level' => $yearLabel,
            'section' => $this->section,
            'verification_status' => $verificationStatus,
            'account_status' => $accountStatus,
        ];
    }

    private function displayVerificationStatus(StudentStatus $status): string
    {
        if ($status === StudentStatus::Suspended) {
            return StudentStatus::Verified->value;
        }

        return $status->value;
    }
}
