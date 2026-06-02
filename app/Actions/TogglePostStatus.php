<?php

namespace App\Actions;

use App\Enums\PostStatus;
use App\Models\Post;

class TogglePostStatus
{
    public function execute(Post $post): void
    {
        if ($post->status === PostStatus::Flagged) {
            $newStatus = PostStatus::Safe;
        } else {
            $newStatus = PostStatus::Flagged;
        }

        $post->update([
            'status' => $newStatus,
        ]);
    }
}