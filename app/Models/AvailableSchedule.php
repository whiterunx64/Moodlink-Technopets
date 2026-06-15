<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'datetime' => 'datetime',
    ];

    protected function isTaken(): Attribute
    {
        return Attribute::make(
            get: fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn(bool $value) => ['isTaken' => $value ? 'true' : 'false'],
        );
    }

    /**
     * Filter schedules by isTaken status.
     */
    public function scopeWhereIsTaken(Builder $query, bool $value): Builder
    {
        return $query->whereRaw('"isTaken" = ?::boolean', [$value]);
    }

    /**
     * Get only available (not taken) schedules ordered by datetime.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $this->scopeWhereIsTaken($query, false)->orderBy('datetime');
    }

    /**
     * Data structure for UI slot display.
     */
    public function toSlotData(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->datetime->format('M d, Y'),
            'startTime' => $this->datetime->format('h:i A'),
        ];
    }
}
