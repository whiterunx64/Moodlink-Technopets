<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int                        $id
 * @property bool                       $isTaken
 * @property \Illuminate\Support\Carbon $datetime
 *
 * @method static Builder|AvailableSchedule whereIsTaken(bool $value)
 * @method static Builder|AvailableSchedule available()
 */
class AvailableSchedule extends Model
{
    protected $table = 'available_schedules';

    public $timestamps = false;

    protected $fillable = [
        'datetime',
        'isTaken',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    protected function isTaken(): Attribute
    {
        return Attribute::make(
            get: fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn(bool $value) => ['isTaken' => $value ? 'true' : 'false'],
        );
    }

    public function scopeWhereIsTaken(Builder $query, bool $value): Builder
    {
        return $query->whereRaw('"isTaken" = ?::boolean', [$value]);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $this->scopeWhereIsTaken($query, false)->orderBy('datetime');
    }

    public static function getAvailableSlotsList(): Collection
    {
        return static::query()
            ->available()
            ->get()
            ->map(fn(AvailableSchedule $schedule): array => [
                'id' => $schedule->id,
                'date' => $schedule->datetime->format('M d, Y'),
                'startTime' => $schedule->datetime->format('h:i A'),
            ]);
    }
}
