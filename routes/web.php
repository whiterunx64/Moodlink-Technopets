<?php

use App\Http\Controllers\AppointmentController;
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
    Route::post('/user-accounts/{student}/register', [UserAccountController::class, 'register'])
        ->name('user-accounts.register');
    Route::patch('/user-accounts/{student}/status', [UserAccountController::class, 'updateStatus'])
        ->name('user-accounts.update-status');

    Route::get('/post-management', [PostManagementController::class, 'index'])
        ->name('post-management.index');
    Route::patch('/post-management/{post}/toggle-status', [PostManagementController::class, 'toggleFlag'])
        ->name('post-management.toggle-status');

    Route::get('/summary-reports', [SummaryReportController::class, 'index'])->name('summary-reports.index');
    Route::get('/summary-reports/section/{section}', [SummaryReportController::class, 'showSection'])->name('summary-reports.section');
    Route::get('/summary-reports/students/{studentId}', [SummaryReportController::class, 'showStudent'])->name('summary-reports.student');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::patch('/appointments/{appointment}/approve', [AppointmentController::class, 'approve'])
        ->name('appointments.approve');
    Route::patch('/appointments/{appointment}/deny', [AppointmentController::class, 'deny'])
        ->name('appointments.deny');
    Route::patch('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])
        ->name('appointments.complete');
    Route::post('/appointments/schedules', [AppointmentController::class, 'storeSchedule'])
        ->name('appointments.schedules.store');
    Route::delete('/appointments/schedules/{schedule}', [AppointmentController::class, 'destroySchedule'])
        ->name('appointments.schedules.destroy');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/change-metadata', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/account', [ProfileController::class, 'destroy'])->name('profile.account.delete');
});

require __DIR__ . '/auth.php';
