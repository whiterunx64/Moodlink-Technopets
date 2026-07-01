<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReport extends Model
{
    protected $table = 'reported_post';

    public $timestamps = false;

    protected $fillable = ['post_id', 'student_id', 'reason', 'comment'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
