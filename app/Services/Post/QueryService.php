<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Models\PendingPost;
use App\Models\Post;
use App\Models\PostReport;
use App\Support\PhTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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

    /**
     * All pending posts ordered newest-first, with student info.
     *
     * @return list<array<string, mixed>>
     */
    public function pendingPostList(): array
    {
        return PendingPost::query()
            ->with('student')
            ->get()
            ->map(fn(PendingPost $post): array => [
                'id'             => $post->id,
                'content'        => $post->content,
                'mood'           => $post->mood,
                'date'           => $post->created_at ? PhTime::fromUtc($post->created_at)->format('M d, Y') : '',
                'time'           => $post->created_at ? PhTime::fromUtc($post->created_at)->format('h:i A') : '',
                'program'        => $post->student?->program ?? '',
                'anonymous_name' => $post->student?->anonymous_name ?? 'Anonymous',
            ])
            ->values()
            ->all();
    }

    /**
     * Posts that have at least 3 entries in reported_post, with reporter details.
     *
     * @return list<array<string, mixed>>
     */
    public function reportedPostList(): array
    {
        $posts = Post::query()
            ->with([
                'student',
                'reports' => fn($q) => $q->with('reporter'),
            ])
            ->withCount('reports')
            ->has('reports', '>=', 3)
            ->orderByDesc('reports_count')
            ->get();

        return $posts
            ->map(fn(Post $post) => $this->formatReportedPost($post))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReportedPost(Post $post): array
    {
        $breakdown = [
            'harassment'       => 0,
            'offensive_language' => 0,
            'bullying'         => 0,
            'false_information' => 0,
            'spam'             => 0,
            'other'            => 0,
        ];

        foreach ($post->reports as $report) {
            $key = $this->reasonToKey((string) $report->reason);
            $breakdown[$key] = ($breakdown[$key] ?? 0) + 1;
        }

        $topKey = 'other';
        $maxCount = 0;
        foreach ($breakdown as $key => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $topKey = $key;
            }
        }

        $latestAt = $post->reports->max('created_at');
        $latestDate = $latestAt
            ? Carbon::parse($latestAt)->format('M d, Y')
            : '';

        $reporters = $post->reports
            ->values()
            ->map(fn(PostReport $r, int $i): array => [
                'id'             => $i + 1,
                'name'           => $r->reporter?->name ?? 'Unknown',
                'student_number' => $r->reporter?->student_number ?? '',
                'program'        => $r->reporter?->program ?? '',
                'date_reported'  => $r->created_at?->format('M d, Y') ?? '',
                'reason'         => $this->keyToReason($this->reasonToKey((string) $r->reason)),
                'comment'        => $r->comment ?: null,
            ])
            ->all();

        $content = $post->content ?? '';
        $preview = mb_strlen($content) > 150
            ? mb_substr($content, 0, 150) . '...'
            : $content;

        $status = match ($post->status?->value) {
            'flagged'  => 'flagged',
            'archived' => 'resolved',
            default    => 'pending',
        };

        return [
            'id'                 => $post->id,
            'anonymous_name'     => $post->student?->anonymous_name ?? 'Anonymous',
            'post_preview'       => $preview,
            'full_content'       => $content,
            'date_posted'        => $post->display_date,
            'program'            => $post->student?->program ?? '',
            'mood'               => $post->mood?->value ?? '',
            'report_count'       => $post->reports_count,
            'top_reason'         => $this->keyToReason($topKey),
            'latest_report_date' => $latestDate,
            'status'             => $status,
            'reason_breakdown'   => $breakdown,
            'reporters'          => $reporters,
        ];
    }

    private function reasonToKey(string $reason): string
    {
        return match (strtolower(trim($reason))) {
            'harassment'                        => 'harassment',
            'offensive language', 'offensive_language' => 'offensive_language',
            'bullying'                          => 'bullying',
            'false information', 'false_information'   => 'false_information',
            'spam'                              => 'spam',
            default                             => 'other',
        };
    }

    private function keyToReason(string $key): string
    {
        return match ($key) {
            'harassment'        => 'Harassment',
            'offensive_language' => 'Offensive Language',
            'bullying'          => 'Bullying',
            'false_information' => 'False Information',
            'spam'              => 'Spam',
            default             => 'Other',
        };
    }
}
