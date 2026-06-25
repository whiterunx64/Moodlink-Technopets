<?php

namespace App\Services;

use App\Enums\PostMood;
use App\Enums\PostStatus;
use App\Support\PhTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function round;
use function in_array;
use function array_sum;

final class AdminDashboardService
{
  private const MOOD_COLORS = [
    PostMood::Excited->value => 'bg-green-400',
    PostMood::Content->value => 'bg-blue-400',
    PostMood::Stressed->value => 'bg-yellow-400',
    PostMood::Drained->value => 'bg-red-400',
  ];

  private const TREND_PERIODS = ['Today', 'Weekly', 'Monthly'];

  public function getDashboardData(): array
  {
    $todayStart = PhTime::todayStartUtc();

    $moodLogsToday = DB::table('posts')
      ->join('students', 'posts.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->where('posts.datetime', '>=', $todayStart)
      ->count();

    $activeStudents = DB::table('students')
      ->where('status', 'verified')
      ->count();

    $flaggedPosts = DB::table('posts')
      ->join('students', 'posts.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->where('posts.status', PostStatus::Flagged->value)
      ->where('posts.datetime', '>=', $todayStart)
      ->count();

    $escalationRequests = DB::table('appointments')
      ->join('students', 'appointments.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->where('appointments.status', 'Pending')
      ->count();

    $moodEntries = DB::table('posts')
      ->join('students', 'posts.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->whereIn('posts.status', [PostStatus::Safe->value, PostStatus::Flagged->value])
      ->where('posts.datetime', '>=', $todayStart)
      ->orderByDesc('posts.datetime')
      ->limit(20)
      ->select(
        'posts.id',
        'posts.mood',
        'posts.content',
        'posts.datetime',
        'posts.status',
        'students.anonymous_name',
        'students.first_name',
        'students.last_name'
      )
      ->get()
      ->map(function ($row) {
        $time = PhTime::fromUtc($row->datetime);

        return [
          'id' => $row->id,
          'mood' => $row->mood,
          'message' => $row->content,
          'time' => $time->format('g:i A'),
          'flagged' => $row->status === PostStatus::Flagged->value,
          'name' => $row->status === PostStatus::Flagged->value
            ? trim(" ({$row->anonymous_name}) {$row->first_name} {$row->last_name}")
            : ($row->anonymous_name ?: 'Anonymous (not set)'),
        ];
      });

    $appointments = DB::table('appointments')
      ->join('students', 'appointments.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->whereIn('appointments.status', ['Pending', 'Scheduled'])
      ->where('appointments.datetime', '>=', $todayStart)
      ->orderBy('appointments.datetime')
      ->limit(5)
      ->select(
        'appointments.id',
        'appointments.status',
        'appointments.datetime',
        'students.anonymous_name',
        'students.first_name',
        'students.last_name'
      )
      ->get()
      ->map(function ($row) {
        $date = PhTime::fromUtc($row->datetime);

        // Always ensure name is not null
        $name = $row->anonymous_name ?: trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        $name = $name ?: 'Anonymous';

        return [
          'id' => $row->id,
          'name' => $name,
          'time' => $date->format('g:i A'),
          'date' => $date->isToday()
            ? 'Today'
            : ($date->isTomorrow() ? 'Tomorrow' : $date->format('M j')),
          'label' => $row->status === 'Pending' ? 'Urgent' : 'Consultation',
          'style' => $row->status === 'Pending'
            ? 'bg-red-50 text-red-500'
            : 'bg-blue-50 text-blue-500',
        ];
      });

    return [
      'mood_logs_today' => $moodLogsToday,
      'active_students' => $activeStudents,
      'flagged_posts' => $flaggedPosts,
      'escalation_requests' => $escalationRequests,
      'mood_entries' => $moodEntries,
      'appointments' => $appointments,
    ];
  }

  public function getMoodTrends(?string $period = null, ?string $program = null): array
  {
    $period = in_array($period, self::TREND_PERIODS, true) ? $period : 'Today';

    $programs = Cache::remember(
      'dashboard.trend_programs',
      now()->addMinutes(5),
      fn() =>
      DB::table('students')
        ->where('status', 'verified')
        ->whereNotNull('program')
        ->distinct()
        ->orderBy('program')
        ->pluck('program')
        ->all()
    );

    $program = ($program !== null && in_array($program, $programs, true)) ? $program : 'All';

    $query = DB::table('posts')
      ->join('students', 'posts.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->whereIn('posts.status', [PostStatus::Safe->value, PostStatus::Flagged->value])
      ->where('posts.datetime', '>=', $this->periodStart($period));

    if ($program !== 'All') {
      $query->where('students.program', $program);
    }

    $counts = $query
      ->select('posts.mood', DB::raw('COUNT(*) as aggregate'))
      ->groupBy('posts.mood')
      ->pluck('aggregate', 'posts.mood')
      ->all();

    $total = array_sum($counts);

    $distribution = [];
    foreach (self::MOOD_COLORS as $mood => $color) {
      $count = (int) ($counts[$mood] ?? 0);

      $distribution[] = [
        'label' => $mood,
        'pct' => $total > 0 ? (int) round($count / $total * 100) : 0,
        'color' => $color,
      ];
    }

    return [
      'period' => $period,
      'program' => $program,
      'programs' => ['All', ...$programs],
      'total' => $total,
      'distribution' => $distribution,
    ];
  }

  private function periodStart(string $period): Carbon
  {
    $now = PhTime::now();

    $start = match ($period) {
      'Weekly' => $now->copy()->subDays(6)->startOfDay(),
      'Monthly' => $now->copy()->subDays(29)->startOfDay(),
      default => $now->copy()->startOfDay(),
    };

    return $start->utc();
  }
}