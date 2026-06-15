<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

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
        // Note: 'isTaken' is NOT cast as 'boolean' here —
        // it's handled manually below to play nicely with Postgres.
    ];

    /**
     * Accessor: always return a real PHP bool, regardless of
     * whether the underlying value came from the DB (true/false)
     * or is still an unsaved Expression on this instance.
     */
    public function getIsTakenAttribute($value): bool
    {
        if ($value instanceof Expression) {
            return $value->getValue(DB::connection()->getQueryGrammar()) === 'true';
        }

        return (bool) $value;
    }

    /**
     * Mutator: convert PHP bool -> raw SQL literal so it's sent
     * to Postgres as `true`/`false`, not `1`/`0`.
     */
    public function setIsTakenAttribute($value): void
    {
        $this->attributes['isTaken'] = DB::raw($value ? 'true' : 'false');
    }

    /**
     * Query scope for safely filtering on isTaken.
     * Usage: AvailableSchedule::whereIsTaken(false)->get();
     */
    public function scopeWhereIsTaken(Builder $query, bool $value): Builder
    {
        return $query->whereRaw('"isTaken" = ?::boolean', [$value]);
    }
}