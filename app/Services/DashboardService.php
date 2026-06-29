<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Models\Appointment;
use App\Models\Post;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    private const TREND_PERIODS = [
        'Today',
        'Weekly',
        'Monthly',
    ];

    /** Memoized headline counts, so the four accessors below share one query. */
    private ?object $headline = null;

    public function moodLogsToday(): int
    {
        return (int) $this->headlineCounts()->mood_logs_today;
    }

    public function activeStudents(): int
    {
        return (int) $this->headlineCounts()->active_students;
    }

    public function flaggedPostsToday(): int
    {
        return (int) $this->headlineCounts()->flagged_posts;
    }

    public function escalationRequests(): int
    {
        return (int) $this->headlineCounts()->escalation_requests;
    }

    /**
     * The four headline counts in a single round-trip. They span four tables, so —
     * unlike Appointment::tabCounts (one table, SUM(CASE)) — they are gathered as
     * scalar sub-selects that reuse each model's scopes. Memoized per request.
     */
    private function headlineCounts(): object
    {
        return $this->headline ??= DB::query()
            ->selectSub(
                StatusDay::query()
                    ->whereStudentIsVerified()
                    ->recordedOnOrAfter(PhTime::startOfDaysAgo(0))
                    ->whereNotNull('mood')
                    ->selectRaw('count(*)'),
                'mood_logs_today',
            )
            ->selectSub(
                Student::query()->whereStatusIsVerified()->selectRaw('count(*)'),
                'active_students',
            )
            ->selectSub(
                Post::query()
                    ->fromVerifiedStudents()
                    ->where('status', PostStatus::Flagged->value)
                    ->where('datetime', '>=', PhTime::todayStartUtc())
                    ->selectRaw('count(*)'),
                'flagged_posts',
            )
            ->selectSub(
                Appointment::query()
                    ->whereHas('student', fn($query) => $query->whereStatusIsVerified())
                    ->where('status', AppointmentStatus::Pending->value)
                    ->selectRaw('count(*)'),
                'escalation_requests',
            )
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function moodEntries(): array
    {
        return Post::dashboardRecentEntries()
            ->map(fn(Post $post) => $this->formatMoodEntry($post))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMoodEntry(Post $post): array
    {
        return [
            'id' => $post->id,
            'mood' => $post->mood?->value,
            'message' => $post->content,
            'time' => $post->display_time,
            'flagged' => $post->status === PostStatus::Flagged,
            'name' => $this->postStudentName($post),
        ];
    }

    private function postStudentName(Post $post): string
    {
        if ($post->status === PostStatus::Flagged) {
            return trim(
                "({$post->student?->anonymous_name}) {$post->student?->first_name} {$post->student?->last_name}"
            );
        }

        return $post->student?->anonymous_name
            ?: 'Anonymous (not set)';
    }


    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingAppointments(): array
    {
        return Appointment::query()
            ->with('student')
            ->whereHas('student', fn(Builder $query) => $query->whereStatusIsVerified())
            ->where('status', AppointmentStatus::Scheduled->value)
            ->where('datetime', '>=', PhTime::todayStartUtc())
            ->orderBy('datetime')
            ->limit(5)
            ->get()
            ->map(fn(Appointment $appointment) => $this->formatAppointment($appointment))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAppointment(Appointment $appointment): array
    {
        $date = PhTime::fromUtc($appointment->datetime);

        return [
            'id' => $appointment->id,
            'name' => $this->appointmentStudentName($appointment),
            'time' => $appointment->display_time,
            'date' => $this->appointmentDateLabel($date),
            'label' => $this->appointmentLabel($appointment),
            'style' => $this->appointmentStyle($appointment),
        ];
    }


    private function appointmentStudentName(Appointment $appointment): string
    {
        $student = $appointment->student;

        return $student?->anonymous_name
            ?: trim("{$student?->first_name} {$student?->last_name}")
            ?: 'Anonymous';
    }


    private function appointmentDateLabel($date): string
    {
        return match (true) {
            $date->isToday() => 'Today',
            $date->isTomorrow() => 'Tomorrow',
            default => $date->format('M j'),
        };
    }


    private function appointmentLabel(Appointment $appointment): string
    {
        return $appointment->status === AppointmentStatus::Pending
            ? 'Urgent'
            : 'Consultation';
    }


    private function appointmentStyle(Appointment $appointment): string
    {
        return $appointment->status === AppointmentStatus::Pending
            ? 'bg-red-50 text-red-500'
            : 'bg-blue-50 text-blue-500';
    }


    /**
     * @return array<string, mixed>
     */
    public function moodTrends(?string $period, ?string $program): array
    {
        $period = $this->resolvePeriod($period);

        $programs = $this->availablePrograms();

        $program = $this->resolveProgram($program, $programs);

        $counts = StatusDay::moodCountsSince(
            $this->trendStartDate($period),
            $program === 'All' ? null : $program
        );

        return [
            'period' => $period,
            'program' => $program,
            'programs' => ['All', ...$programs],
            'total' => $counts->sum(),
            'distribution' => $this->moodDistribution($counts),
        ];
    }


    private function resolvePeriod(?string $period): string
    {
        return in_array($period, self::TREND_PERIODS, true)
            ? $period
            : 'Today';
    }


    private function availablePrograms(): array
    {
        return Cache::remember(
            'dashboard.trend_programs',
            300,
            fn() => Student::verifiedPrograms()
        );
    }


    /**
     * @return array<string, mixed>
     */
    private function resolveProgram(?string $program, array $programs): string
    {
        return in_array($program, $programs, true)
            ? $program
            : 'All';
    }


    private function trendStartDate(string $period)
    {
        return PhTime::startOfDaysAgo(match ($period) {
            'Weekly' => 6,
            'Monthly' => 29,
            default => 0,
        });
    }


    private function moodDistribution($counts): array
    {
        $total = $counts->sum();

        return collect(PostMood::availableMoods())
            ->map(fn(PostMood $mood) => [
                'label' => $mood->value,
                'pct' => $total
                    ? round(($counts[$mood->value] ?? 0) / $total * 100)
                    : 0,
                'color' => $mood->color(),
            ])
            ->all();
    }
}