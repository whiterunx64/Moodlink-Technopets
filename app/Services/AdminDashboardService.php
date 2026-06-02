<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class AdminDashboardService
{
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
          'status' => $row->status,
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
        'students.anonymous_name'
      )
      ->get()
      ->map(function ($row) {
        $date = Carbon::parse($row->datetime)
          ->setTimezone('Asia/Manila');

        return [
          'id' => $row->id,
          'name' => $row->anonymous_name,
          'time' => $date->format('g:i A'),
          'date' => $date->isToday()
            ? 'Today'
            : ($date->isTomorrow()
              ? 'Tomorrow'
              : $date->format('M j')),
          'label' => $row->status === 'Pending'
            ? 'Urgent'
            : 'Consultation',
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
}