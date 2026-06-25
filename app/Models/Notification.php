<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\PhTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A user-facing notification, consumed by the student app.
 *
 * @property int $id
 * @property int $student_id  Recipient (students.id).
 * @property string|null $content
 * @property string $type
 * @property bool $is_seen
 * @property string|null $title
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

    /** Sentinel recipient id for notifications shown in the admin header inbox. */
    public const ADMIN_INBOX_ID = 1;

    /**
     * Notifications shown in the admin header inbox (id 1 only).
     *
     * @return Collection<int, self>
     */
    public static function adminInbox(): Collection
    {
        return self::query()
            ->where('student_id', self::ADMIN_INBOX_ID)
            ->orderByDesc('datetime')
            ->get();
    }

    public static function existsWithMarker(string $type, string $marker): bool
    {
        return self::query()
            ->where('type', $type)
            ->where('content', 'like', '%' . $marker . '%')
            ->exists();
    }

    public static function deleteWithMarker(string $type, string $marker): void
    {
        self::query()
            ->where('type', $type)
            ->where('content', 'like', '%' . $marker . '%')
            ->delete();
    }

    public static function adminAlert(string $title, string $content, string $type): self
    {
        return self::adminInboxOrStudent(self::ADMIN_INBOX_ID, $title, $content, $type);
    }

    public static function studentAlert(int $studentId, string $title, string $content, string $type): self
    {
        return self::adminInboxOrStudent($studentId, $title, $content, $type);
    }

    private static function adminInboxOrStudent(int $studentId, string $title, string $content, string $type): self
    {
        return self::create([
            'student_id' => $studentId,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'is_seen' => DB::raw('false'),
            'datetime' => PhTime::nowUtc(),
        ]);
    }
}