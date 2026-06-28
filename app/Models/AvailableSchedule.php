<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDateTimeDisplay;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $takenBy
 * @property \Illuminate\Support\Carbon $datetime
 *
 * @property-read string $display_date
 * @property-read string $display_time
 * 
 * @mixin HasDateTimeDisplay
 */

class AvailableSchedule extends Model
{
    use HasDateTimeDisplay;

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

    protected function displayDate(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->phFormat('M d, Y'),
        );
    }

    protected function displayTime(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->phFormat('h:i A'),
        );
    }

}