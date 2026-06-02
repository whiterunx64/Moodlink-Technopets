<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @extends Model
 *
 * @property int $id
 * @property int $student_id
 * @property string $content
 * @property string $mood
 * @property \Illuminate\Support\Carbon $datetime
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read BuildStudent|null $student
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\BuildStudent, \App\Models\BuildPost> student()
 */
class BuildPost extends Model
{
    // Database table used by this model
    protected $table = 'posts';

    const UPDATED_AT = null; 
    const CREATED_AT = null; 

    protected $fillable = [
        'student_id',
        'content',
        'mood',
        'datetime',
        'status',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'mood' => 'string',
        'status' => 'string',
    ];

    /** @param Builder<BuildPost> $query */
    public function scopeFromVerifiedStudents(Builder $query): Builder
    {
        return $query->whereHas('student', fn(Builder $q) => $q->where('status', 'verified'));
    }

    /**
     * @return BelongsTo<BuildStudent, BuildPost>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(BuildStudent::class, 'student_id');
    }
}