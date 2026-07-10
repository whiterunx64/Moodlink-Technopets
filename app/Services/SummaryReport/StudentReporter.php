<?php

declare(strict_types=1);

namespace App\Services\SummaryReport;

use App\Enums\PostMood;
use App\Models\Post;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use Illuminate\Support\Collection;

use function array_slice;
use function count;

class StudentReporter
{
    /**
     * @return array<string, mixed>
     */
    public function build(Student $student, int $trendDays): array
    {
        $moodEntryCounts = StatusDay::moodEntryCountsForStudent($student->id);
        $postCounts = Post::totalAndFlaggedCountsForStudent($student->id);
        $trendData = $this->dailyMoodTrend($student->id, $trendDays);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'full_name' => $student->anonymous_name ?? $student->name,
            'student_number' => $student->student_number,
            'year_level' => $student->year_level_label,
            'program' => $student->program,
            'initials' => $student->studentNameInitials,
            'mood_summary' => [
                'excited' => $moodEntryCounts->get(PostMood::Excited->value, 0),
                'content' => $moodEntryCounts->get(PostMood::Content->value, 0),
                'stressed' => $moodEntryCounts->get(PostMood::Stressed->value, 0),
                'drained' => $moodEntryCounts->get(PostMood::Drained->value, 0),
            ],
            'summary_stats' => [
                'total_mood_entries' => $moodEntryCounts->sum(),
                'total_posts' => $postCounts->total,
                'flagged_posts' => $postCounts->flagged,
            ],
            'trend' => $this->trendDirection($trendData),
            'trend_data' => $trendData,
            'recent_entries' => StatusDay::recentMoodEntriesForStudent($student->id)
                ->map(fn(StatusDay $entry): array => [
                    'id' => $entry->id,
                    'mood' => $entry->mood->value,
                    'content' => $entry->journal,
                    'date' => $entry->date->format('M j, Y'),
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array{
     *     date: string,
     *     label: string,
     *     score: float|null,
     *     dominant_mood: string|null,
     *     posts: list<array{id: int, mood: string, content: string|null, time: string}>
     * }>
     */
    private function dailyMoodTrend(int $studentId, int $trendDays): array
    {
        $windowStart = PhTime::now()->startOfDay()->subDays($trendDays - 1);

        $postsByDate = Post::moodPostsForStudentSince($studentId, $windowStart->utc())
            ->groupBy(fn(Post $post): string => PhTime::fromUtc($post->datetime)->toDateString());

        return collect(range(0, $trendDays - 1))
            ->map(function (int $dayOffset) use ($windowStart, $postsByDate, $trendDays): array {
                $day = $windowStart->copy()->addDays($dayOffset);
                /** @var Collection<int, Post> $posts */
                $posts = $postsByDate->get($day->toDateString()) ?? new Collection();

                return [
                    'date' => $day->toDateString(),
                    'label' => $trendDays === 7 ? $day->format('D') : $day->format('M j'),
                    'score' => $posts->isEmpty()
                        ? null
                        : round($posts->avg(fn(Post $post): float => $post->mood->wellbeingScore()), 1),
                    'dominant_mood' => $this->dominantMood($posts),
                    'posts' => $posts
                        ->map(fn(Post $post): array => [
                            'id' => $post->id,
                            'mood' => $post->mood->value,
                            'content' => $post->content,
                            'time' => PhTime::fromUtc($post->datetime)->format('g:i A'),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Post>  $posts
     */
    private function dominantMood(Collection $posts): ?string
    {
        if ($posts->isEmpty()) {
            return null;
        }

        return $posts
            ->groupBy(fn(Post $post): string => $post->mood->value)
            ->sortByDesc(fn(Collection $group): array => [
                $group->count(),
                $group->first()->mood->wellbeingScore(),
            ])
            ->keys()
            ->first();
    }

    /**
     * @param  list<array{score: float|null}>  $trendData
     */
    private function trendDirection(array $trendData): string
    {
        $scores = array_values(array_filter(
            array_column($trendData, 'score'),
            fn(?float $score): bool => $score !== null,
        ));

        if (count($scores) < 2) {
            return 'Stable';
        }

        $midpoint = intdiv(count($scores), 2);
        $earlierHalf = array_slice($scores, 0, $midpoint);
        $laterHalf = array_slice($scores, $midpoint);

        $delta = (array_sum($laterHalf) / count($laterHalf))
            - (array_sum($earlierHalf) / count($earlierHalf));

        return match (true) {
            $delta > 0.25 => 'Improving',
            $delta < -0.25 => 'Declining',
            default => 'Stable',
        };
    }
}
