<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int                        $id
 * @property int|null                   $takenBy
 * @property \Illuminate\Support\Carbon $datetime
 */
class AvailableSchedule extends Model
{
    protected $table = 'available_schedules';

    public $timestamps = false;

    protected $fillable = [
        'datetime',
        'takenBy',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    public static function getAvailableSlotsList(): Collection
    {
        return static::query()
            ->orderBy('datetime')
            ->get()
            ->map(fn(AvailableSchedule $schedule): array => [
                'id' => $schedule->id,
                'date' => $schedule->datetime->format('M d, Y'),
                'startTime' => $schedule->datetime->format('h:i A'),
                'taken' => $schedule->takenBy !== null,
            ]);
    }
}
