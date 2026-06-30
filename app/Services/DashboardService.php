<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Enums\StudentStatus;
use App\Models\Appointment;
use App\Models\Post;
use App\Models\StatusDay;
use App\Models\Student;
use App\Support\PhTime;
use Carbon\Carbon;
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

    private const STAT_PERIODS = ['today', 'week', 'month'];

    private const ACTIVITY_TABS = ['feed', 'appointments', 'flagged'];

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
     * @return array<int, array<string, mixed>>
     */
    public function flaggedMoodEntries(): array
    {
        return Post::dashboardRecentEntries()
            ->filter(fn(Post $post) => $post->status === PostStatus::Flagged)
            ->map(fn(Post $post) => $this->formatMoodEntry($post))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMoodEntry(Post $post): array
    {
        $student = $post->student;

        return [
            'id' => $post->id,
            'mood' => $post->mood?->value,
            'message' => $post->content,
            'time' => $post->display_time,
            'flagged' => $post->status === PostStatus::Flagged,
            'name' => trim("{$student?->first_name} {$student?->last_name}") ?: 'Unknown',
            'anonymous_name' => $student?->anonymous_name ?: 'Anonymous',
        ];
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

    public function resolveStatPeriod(?string $period): string
    {
        return in_array($period, self::STAT_PERIODS, true) ? $period : 'today';
    }

    public function resolveActivityTab(?string $tab): string
    {
        return in_array($tab, self::ACTIVITY_TABS, true) ? $tab : 'feed';
    }

    private function statStartDate(string $period): Carbon
    {
        return match ($period) {
            'week' => PhTime::now()->startOfWeek(),
            'month' => PhTime::now()->startOfMonth(),
            default => PhTime::now()->startOfDay(),
        };
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

    /**
     * @return array<string, mixed>
     */
    public function moodLogsBreakdown(string $period = 'today'): array
    {
        $start = $this->statStartDate($period);

        $counts = StatusDay::query()
            ->whereStudentIsVerified()
            ->recordedOnOrAfter($start)
            ->whereNotNull('mood')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN mood IN ('Content', 'Excited') THEN 1 ELSE 0 END) as safe")
            ->selectRaw("SUM(CASE WHEN mood IN ('Stressed', 'Drained') THEN 1 ELSE 0 END) as flagged")
            ->first();

        $leading = StatusDay::query()
            ->whereStudentIsVerified()
            ->recordedOnOrAfter($start)
            ->whereNotNull('mood')
            ->selectRaw('mood, COUNT(*) as cnt')
            ->groupBy('mood')
            ->orderByDesc('cnt')
            ->value('mood');

        return [
            'total' => (int) ($counts?->total ?? 0),
            'safe' => (int) ($counts?->safe ?? 0),
            'flagged' => (int) ($counts?->flagged ?? 0),
            'leading' => $leading,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentsBreakdown(): array
    {
        $counts = DB::table('students')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [StudentStatus::Verified->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending', [StudentStatus::Pending->value, StudentStatus::Unverified->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as suspended', [StudentStatus::Suspended->value])
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'active' => (int) ($counts?->active ?? 0),
            'pending' => (int) ($counts?->pending ?? 0),
            'suspended' => (int) ($counts?->suspended ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function postsBreakdown(string $period = 'today'): array
    {
        $start = $this->statStartDate($period)->utc();

        $counts = Post::query()
            ->fromVerifiedStudents()
            ->where('datetime', '>=', $start)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as safe', [PostStatus::Safe->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as flagged', [PostStatus::Flagged->value])
            ->first();

        $total = (int) ($counts?->total ?? 0);
        $flagged = (int) ($counts?->flagged ?? 0);

        return [
            'total' => $total,
            'safe' => (int) ($counts?->safe ?? 0),
            'flagged' => $flagged,
            'flag_rate' => $total > 0 ? round($flagged / $total * 100) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function appointmentsBreakdown(string $period = 'today'): array
    {
        $start = $this->statStartDate($period)->utc();

        $counts = Appointment::query()
            ->whereHas('student', fn(Builder $q) => $q->whereStatusIsVerified())
            ->where('datetime', '>=', $start)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as scheduled', [AppointmentStatus::Scheduled->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [AppointmentStatus::Pending->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as missed', [AppointmentStatus::Missed->value])
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'scheduled' => (int) ($counts?->scheduled ?? 0),
            'pending' => (int) ($counts?->pending ?? 0),
            'missed' => (int) ($counts?->missed ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentActivityAppointments(): array
    {
        return Appointment::query()
            ->with('student')
            ->whereHas('student', fn(Builder $q) => $q->whereStatusIsVerified())
            ->whereIn('status', [
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Pending->value,
                AppointmentStatus::Missed->value,
            ])
            ->orderByDesc('datetime')
            ->limit(10)
            ->get()
            ->map(fn(Appointment $apt) => [
                'id' => $apt->id,
                'name' => trim("{$apt->student?->first_name} {$apt->student?->last_name}") ?: 'Unknown',
                'anonymous_name' => $apt->student?->anonymous_name ?: 'Anonymous',
                'context' => $apt->context,
                'time' => $apt->display_time,
                'status' => $apt->status->value,
            ])
            ->all();
    }
}