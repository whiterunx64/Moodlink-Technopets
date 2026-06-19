<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A user-facing notification, consumed by the student app.
 *
 * @property int $id
 * @property int $student_id  Recipient (students.id).
 * @property string|null $content
 * @property string $type
 * @property bool $is_seen
 * @property string|null $title
 * @property Carbon $datetime
 */
class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'content',
        'type',
        'is_seen',
        'title',
        'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_seen' => 'boolean',
            'datetime' => 'datetime',
        ];
    }
}
