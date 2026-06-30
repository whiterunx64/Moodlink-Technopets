<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostStatus;
use App\Exceptions\PostModerationException;
use App\Models\Post;
use App\Models\PostReport;
use App\Services\Post\QueryService;
use App\Services\Post\Validator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PostManager
{
    public function __construct(
        private readonly Validator $validator,
        private readonly QueryService $queries,
    ) {
    }

    // ─────────────────────────────────────────────────────────────
    // Public Method — QueryService.php
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $filters
     */
    public function paginatedPostList(array $filters): LengthAwarePaginator
    {
        return $this->queries->paginatedPostList($filters);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        return $this->queries->statusCounts();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function reportedPostList(): array
    {
        return $this->queries->reportedPostList();
    }

    /**
     * Clear all reports for a post and set its status to safe.
     */
    public function markReportedSafe(Post $post): void
    {
        PostReport::where('post_id', $post->id)->delete();
        $post->update(['status' => PostStatus::Safe]);
    }

    /**
     * Clear all reports for a post and set its status to flagged.
     */
    public function markReportedFlagged(Post $post): void
    {
        PostReport::where('post_id', $post->id)->delete();
        $post->update(['status' => PostStatus::Flagged]);
    }

    // ─────────────────────────────────────────────────────────────
    // Public Method — Validator.php
    // ─────────────────────────────────────────────────────────────

    /**
     * @throws PostModerationException when the post is already flagged.
     */
    public function flagPost(Post $post): void
    {
        $this->validator->ensurePostCanBeFlagged($post);

        $post->update(['status' => PostStatus::Flagged]);
    }

    /**
     * @throws PostModerationException when the post is not flagged.
     */
    public function unflagPost(Post $post): void
    {
        $this->validator->ensurePostCanBeUnflagged($post);

        $post->update(['status' => PostStatus::Safe]);
    }
}
