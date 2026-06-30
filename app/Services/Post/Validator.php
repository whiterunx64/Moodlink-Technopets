<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Enums\PostStatus;
use App\Exceptions\PostModerationException;
use App\Models\Post;

class Validator
{
    /**
     * @throws PostModerationException when the post is not Safe.
     */
    public function ensurePostCanBeFlagged(Post $post): void
    {
        if ($post->status !== PostStatus::Safe) {
            throw PostModerationException::postIsNotSafeAndCannotBeFlagged();
        }
    }

    /**
     * @throws PostModerationException when the post is not Flagged.
     */
    public function ensurePostCanBeUnflagged(Post $post): void
    {
        if ($post->status !== PostStatus::Flagged) {
            throw PostModerationException::postIsNotFlaggedAndCannotBeUnflagged();
        }
    }
}
