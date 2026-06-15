<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => redirect()->route('login'));

//Route::get('/debug-session', function () {
//    abort_unless(app()->environment('local'), 403);
//    return session()->all();
//});

//Route::get('/debug-cache', function (\App\Services\CacheManager $cache) {
//
//    $userId = 'test-id';
//
//    $start = microtime(true);
//
//    $cache->cacheUserData($userId, ['name' => 'test']);
//    $cache->getCachedUserData($userId);
//
//    $duration = microtime(true) - $start;
//
//    return [
//        'time_seconds' => $duration,
//    ];
//});

Route::middleware(['supabase.auth', 'supabase.token'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/user-accounts', [UserAccountController::class, 'index'])
        ->name('user-accounts.index');
    Route::patch('/user-accounts/{student}/status', [UserAccountController::class, 'updateStatus'])
        ->name('user-accounts.update-status');
    Route::get('/post-management', [PostManagementController::class, 'index'])
        ->name('post-management.index');
    Route::patch('/post-management/{post}/toggle-status', [PostManagementController::class, 'toggleFlag'])
        ->name('post-management.toggle-status');
    // TODO: replace closure with AppointmentController once backend is wired
    Route::get('/appointments', function (\Illuminate\Http\Request $req) {
        $tab = $req->input('tab', 'requests');

        $profiles = [
            'JeviXD Reyes' => [
                'initials' => 'JR',
                'section' => 'S002',
                'course' => 'BSCS 3A',
                'yearLevel' => '3rd Year',
                'email' => 'jevixd.r@uni.edu',
                'studentId' => '2021-30045',
                'totalAppointments' => 1,
                'history' => [['context' => 'Social Isolation', 'date' => 'Jun 14, 2026', 'time' => '02:00 PM', 'note' => 'Feeling disconnected from peers since moving to campus.', 'status' => 'Pending']]
            ],
            'Mara Santos' => [
                'initials' => 'MS',
                'section' => 'S001',
                'course' => 'BSIT 1B',
                'yearLevel' => '1st Year',
                'email' => 'mara.s@uni.edu',
                'studentId' => '2025-10023',
                'totalAppointments' => 1,
                'history' => [['context' => 'Anxiety', 'date' => 'Jun 15, 2026', 'time' => '10:00 AM', 'note' => 'Experiencing panic attacks before major exams.', 'status' => 'Pending']]
            ],
            'Jayvee Erandio' => [
                'initials' => 'JE',
                'section' => 'S001',
                'course' => 'BSIT 2A',
                'yearLevel' => '2nd Year',
                'email' => 'jayvee.e@uni.edu',
                'studentId' => '2024-20017',
                'totalAppointments' => 2,
                'history' => [
                    ['context' => 'Academic Stress', 'date' => 'Jun 12, 2026', 'time' => '09:00 AM', 'note' => 'Struggling with final exams and workload overlap.', 'status' => 'Scheduled'],
                    ['context' => 'Peer Conflict', 'date' => 'May 28, 2026', 'time' => '03:00 PM', 'note' => 'Conflict with groupmates in capstone project.', 'status' => 'Completed'],
                ]
            ],
            'Florend Dela Cruz' => [
                'initials' => 'FD',
                'section' => 'S003',
                'course' => 'BSBA 4A',
                'yearLevel' => '4th Year',
                'email' => 'florend.dc@uni.edu',
                'studentId' => '2022-40031',
                'totalAppointments' => 1,
                'history' => [['context' => 'Career Anxiety', 'date' => 'Jun 10, 2026', 'time' => '11:00 AM', 'note' => 'Worried about job prospects after graduation.', 'status' => 'Completed']]
            ],
            'Ashley Martinez' => [
                'initials' => 'AM',
                'section' => 'S002',
                'course' => 'BSIT 4B',
                'yearLevel' => '4th Year',
                'email' => 'ashley.m@uni.edu',
                'studentId' => '2022-40058',
                'totalAppointments' => 1,
                'history' => [['context' => 'Mental Breakdown', 'date' => 'Jun 13, 2026', 'time' => '10:30 AM', 'note' => 'Experiencing burnout and anxiety before thesis defense.', 'status' => 'Rejected']]
            ],
            'Dave Dave' => [
                'initials' => 'DD',
                'section' => 'S001',
                'course' => 'BSCS 2A',
                'yearLevel' => '2nd Year',
                'email' => 'dave.d@uni.edu',
                'studentId' => '2024-20009',
                'totalAppointments' => 1,
                'history' => [['context' => 'Family Issues', 'date' => 'Jun 11, 2026', 'time' => '01:00 PM', 'note' => 'Homesick and dealing with family financial problems.', 'status' => 'Rejected']]
            ],
        ];

        $apt = fn($id, $name, $ctx, $note, $date, $time, $status) => [
            'id' => $id,
            'studentName' => $name,
            'context' => $ctx,
            'note' => $note,
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'studentProfile' => $profiles[$name],
        ];

        $all = [
            'requests' => [
                $apt(1, 'JeviXD Reyes', 'Social Isolation', 'Feeling disconnected from peers since moving to campus.', 'Jun 14, 2026', '02:00 PM', 'Pending'),
                $apt(2, 'Mara Santos', 'Anxiety', 'Experiencing panic attacks before major exams.', 'Jun 15, 2026', '10:00 AM', 'Pending'),
            ],
            'scheduled' => [
                $apt(3, 'Jayvee Erandio', 'Academic Stress', 'Struggling with final exams and workload overlap.', 'Jun 12, 2026', '09:00 AM', 'Scheduled'),
            ],
            'history' => [
                $apt(4, 'Florend Dela Cruz', 'Career Anxiety', 'Worried about job prospects after graduation.', 'Jun 10, 2026', '11:00 AM', 'Completed'),
                $apt(5, 'Jayvee Erandio', 'Peer Conflict', 'Conflict with groupmates in capstone project.', 'May 28, 2026', '03:00 PM', 'Completed'),
            ],
            'rejected' => [
                $apt(6, 'Ashley Martinez', 'Mental Breakdown', 'Experiencing burnout and anxiety before thesis defense.', 'Jun 13, 2026', '10:30 AM', 'Rejected'),
                $apt(7, 'Dave Dave', 'Family Issues', 'Homesick and dealing with family financial problems.', 'Jun 11, 2026', '01:00 PM', 'Rejected'),
            ],
        ];

        return Inertia::render('Appointments/Index', [
            'appointments' => $all[$tab] ?? [],
            'tabCounts' => ['requests' => 2, 'scheduled' => 1, 'history' => 2, 'rejected' => 2],
            'availableSlots' => [
                ['id' => 1, 'date' => 'Jun 12, 2026', 'startTime' => '08:00 AM'],
                ['id' => 2, 'date' => 'Jun 13, 2026', 'startTime' => '01:00 PM'],
                ['id' => 3, 'date' => 'Jun 9, 2026', 'startTime' => '09:00 AM'],
                ['id' => 4, 'date' => 'Jun 10, 2026', 'startTime' => '09:00 AM'],
                ['id' => 5, 'date' => 'Jun 10, 2026', 'startTime' => '11:30 AM'],
            ],
            'filters' => ['tab' => $tab],
        ]);
    })->name('appointments.index');

    Route::get('/summary-reports', [SummaryReportController::class, 'index'])->name('summary-reports.index');
    Route::get('/summary-reports/section/{section}', [SummaryReportController::class, 'showSection'])->name('summary-reports.section');
    Route::get('/summary-reports/students/{studentId}', [SummaryReportController::class, 'showStudent'])->name('summary-reports.student');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/change-metadata', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/account', [ProfileController::class, 'destroy'])->name('profile.account.delete');
});

require __DIR__ . '/auth.php';
