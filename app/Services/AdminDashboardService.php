<?php

namespace App\Services;

use App\Enums\PostMood;
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
    $todayStart = Carbon::today('Asia/Manila')->utc();

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
      ->where('posts.status', 'flagged')
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
      ->whereIn('posts.status', ['safe', 'flagged'])
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
        $time = Carbon::parse($row->datetime)
          ->setTimezone('Asia/Manila');

        return [
          'id' => $row->id,
          'mood' => $row->mood,
          'message' => $row->content,
          'time' => $time->format('g:i A'),
          'flagged' => $row->status === 'flagged',
          'name' => $row->status === 'flagged'
            ? trim("{$row->first_name} {$row->last_name}")
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
        $date = Carbon::parse($row->datetime)
          ->setTimezone('Asia/Manila');

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
      'moodLogsToday' => $moodLogsToday,
      'activeStudents' => $activeStudents,
      'flaggedPosts' => $flaggedPosts,
      'escalationRequests' => $escalationRequests,
      'moodEntries' => $moodEntries,
      'appointments' => $appointments,
    ];
  }

  public function getMoodTrends(?string $period = null, ?string $section = null): array
  {
    $period = in_array($period, self::TREND_PERIODS, true) ? $period : 'Today';

    $sections = Cache::remember(
      'dashboard.trend_sections',
      now()->addMinutes(5),
      fn() =>
      DB::table('students')
        ->where('status', 'verified')
        ->whereNotNull('section')
        ->distinct()
        ->orderBy('section')
        ->pluck('section')
        ->all()
    );

    $section = ($section !== null && in_array($section, $sections, true)) ? $section : 'All';

    $query = DB::table('posts')
      ->join('students', 'posts.student_id', '=', 'students.id')
      ->where('students.status', 'verified')
      ->whereIn('posts.status', ['safe', 'flagged'])
      ->where('posts.datetime', '>=', $this->periodStart($period));

    if ($section !== 'All') {
      $query->where('students.section', $section);
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
      'section' => $section,
      'sections' => ['All', ...$sections],
      'total' => $total,
      'distribution' => $distribution,
    ];
  }

  private function periodStart(string $period): Carbon
  {
    $now = Carbon::now('Asia/Manila');

    $start = match ($period) {
      'Weekly' => $now->copy()->subDays(6)->startOfDay(),
      'Monthly' => $now->copy()->subDays(29)->startOfDay(),
      default => $now->copy()->startOfDay(),
    };

    return $start->utc();
  }
}
