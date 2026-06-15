<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use DomainException;

final class PostManagementService
{
    /**
     * @throws DomainException when the post is already flagged.
     */
    public function flagPost(Post $post): void
    {
        $this->ensurePostCanBeFlagged($post);

        $post->update(['status' => PostStatus::Flagged]);
    }

    /**
     * @throws DomainException when the post is not flagged.
     */
    public function unflagPost(Post $post): void
    {
        $this->ensurePostCanBeUnflagged($post);

        $post->update(['status' => PostStatus::Safe]);
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function ensurePostCanBeFlagged(Post $post): void
    {
        if ($post->status !== PostStatus::Safe) {
            throw new DomainException('This post is already flagged or being processed.');
        }
    }

    private function ensurePostCanBeUnflagged(Post $post): void
    {
        if ($post->status !== PostStatus::Flagged) {
            throw new DomainException('This post is already unflagged or being processed.');
        }
    }
}
