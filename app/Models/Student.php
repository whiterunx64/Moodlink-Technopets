<?php

namespace App\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a Student in the system.
 *
 * @property int $id
 * @property string $section
 * @property string $anonymous_name
 * @property string $status
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 *
 * @method static Builder|Student verified()
 */
class Student extends Model
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

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }

    /**
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'student_id');
    }
}