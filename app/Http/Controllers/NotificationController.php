<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Admin header inbox feed. Returns the notifications (id 1 only) as JSON
     * for the header bell to poll.
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::adminInbox()
            ->map(fn (Notification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'content' => $notification->content,
                'type' => $notification->type,
                'is_seen' => $notification->is_seen,
                'datetime' => $notification->datetime?->toIso8601String(),
            ])
            ->all();

        return response()->json([
            'notifications' => $notifications,
            'unread' => collect($notifications)->where('is_seen', false)->count(),
        ]);
    }

public function markSeen(Notification $notification)
{
    $notification->update([
        'is_seen' => true,
    ]);

    return response()->noContent();
}
}
