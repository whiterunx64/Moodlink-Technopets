<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @extends Model
 *
 * @property int $id
 * @property string $section
 * @property string $anonymous_name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BuildPost> $posts
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\BuildPost, \App\Models\BuildStudent> posts()
 */
class BuildStudent extends Model
{
    // Database table used by this model
    protected $table = 'students';

    protected $fillable = [
        'section',
        'anonymous_name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /** @param Builder<BuildStudent> $query */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }

    /**
     * @return HasMany<BuildPost, BuildStudent>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BuildPost::class, 'student_id');
    }
}