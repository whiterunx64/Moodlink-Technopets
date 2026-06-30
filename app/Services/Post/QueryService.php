<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QueryService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginatedPostList(array $filters): LengthAwarePaginator
    {
        return Post::paginatedListWithFilters($filters)
            ->through(fn(Post $post): array => [
                'id' => $post->id,
                'content' => $post->content,
                'mood' => $post->mood?->value,
                'status' => $post->status?->value,
                'date' => $post->display_date,
                'time' => $post->display_time,
                'program' => $post->student?->program ?? '',
                'anonymous_name' => $post->student?->anonymous_name,
                'last_name' => $post->student?->last_name,
                'first_name' => $post->student?->first_name,
            ]);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $statusCounts = Post::statusCount();

        return [
            'total' => $statusCounts->total,
            'safe' => $statusCounts->safe,
            'flagged' => $statusCounts->flagged,
            'archived' => $statusCounts->archived,
        ];
    }
}
