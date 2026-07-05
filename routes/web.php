<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Route::get('/', fn() => Inertia::render('Landing'))
    ->middleware('throttle:landing')
    ->name('landing');

Route::get(config('supabase-auth.monitoring.health_checks.endpoint'), [HealthController::class, 'check'])
    ->name('health');

Route::middleware(['auth', 'supabase.verify-token', 'supabase.require-admin-access', 'supabase.single-session'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::patch(
    '/notifications/{notification}/seen',
    [NotificationController::class, 'markSeen']
)->name('notifications.seen');

    Route::get('/student-accounts', [UserAccountController::class, 'index'])
        ->name('student-accounts.index');
    Route::post('/student-accounts/{student}/registration', [UserAccountController::class, 'createSupabaseAccount'])
        ->name('student-accounts.registration.store');
    Route::patch('/student-accounts/{student}/registration/accept', [UserAccountController::class, 'acceptRegistration'])
        ->name('student-accounts.registration.accept');
    Route::delete('/student-accounts/{student}/registration', [UserAccountController::class, 'rejectRegistration'])
        ->name('student-accounts.registration.destroy');
    Route::patch('/student-accounts/{student}/restrict-access', [UserAccountController::class, 'restrictAccess'])
        ->name('student-accounts.restrict-access');
    Route::patch('/student-accounts/{student}/restore-access', [UserAccountController::class, 'restoreAccess'])
        ->name('student-accounts.restore-access');
    Route::delete('/student-accounts/{student}', [UserAccountController::class, 'destroy'])
        ->name('student-accounts.destroy');


    Route::get('/post-management', [PostManagementController::class, 'index'])
        ->name('posts.index');

    Route::get('/reported-posts', fn() => redirect()->route('posts.index', ['tab' => 'reported']))
        ->name('reported-posts.index');
    Route::patch('/post-management/{post}/mark-as-flagged', [PostManagementController::class, 'flag'])
        ->name('posts.flag');
    Route::patch('/post-management/{post}/mark-as-unflagged', [PostManagementController::class, 'unflag'])
        ->name('posts.unflag');
    Route::patch('/post-management/{post}/mark-as-unflagged/{status}', [PostManagementController::class, 'unreport'])
        ->name('posts.unreport');
    Route::patch(
    '/pending-posts/{pendingPost}/approve-safe',
    [PostManagementController::class, 'approvePendingAsSafe']
)->name('pending-posts.approve-safe');

Route::patch(
    '/pending-posts/{pendingPost}/approve-flagged',
    [PostManagementController::class, 'approvePendingAsFlagged']
)->name('pending-posts.approve-flagged');

    Route::get('/summary-reports', [SummaryReportController::class, 'index'])
        ->name('reports.index');
    Route::get('/summary-reports/program/{program}', [SummaryReportController::class, 'showProgram'])
        ->name('reports.programs.show');
    Route::get('/summary-reports/students/{student}', [SummaryReportController::class, 'showStudent'])
        ->name('reports.students.show');
    Route::post('/summary-reports/{student}/consult', [SummaryReportController::class, 'consult'])
        ->name('reports.consult');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::patch('/appointments/{appointment}/approve-request', [AppointmentController::class, 'approve'])
        ->name('appointments.approve');
    Route::patch('/appointments/{appointment}/reject-request', [AppointmentController::class, 'reject'])
        ->name('appointments.reject');
    Route::post('/appointments/schedule-slots', [AppointmentController::class, 'storeSlot'])
        ->name('appointments.slots.store');
    Route::delete('/appointments/schedule-slots/{slot}', [AppointmentController::class, 'destroySlot'])
        ->name('appointments.slots.destroy');

    Route::get('/profile', [ProfileController::class, 'settings'])
        ->name('profile.settings');
    Route::patch('/profile/admin', [ProfileController::class, 'updateMetadata'])
        ->name('profile.metadata.update');
    Route::put('/profile/admin/update-password', [ProfileController::class, 'updatePassword'])
        ->middleware('supabase.revalidate')
        ->name('profile.password.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])
        ->name('profile.preferences.update');
    Route::post('/profile/admin/update-avatar', [ProfileController::class, 'updateAvatar'])
        ->name('profile.avatar.update');
    Route::delete('/profile/admin', [ProfileController::class, 'destroyAccount'])
        ->middleware('supabase.revalidate')
        ->name('profile.account.destroy');
});

Route::get('/cron/run', function (Request $request) {
    $cronKey = config('app.cron_key');

    abort_unless(
        filled($cronKey) &&
        hash_equals($cronKey, (string) $request->header('X-Cron-Key')),
        403
    );

    $exitCode = Artisan::call('schedule:run');

    return response()->json([
        'status' => 'ok',
        'exit_code' => $exitCode,
        'output' => Artisan::output(),
        'ran_at' => now()->toIso8601String(),
    ]);
});

Route::get('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])
    ->middleware('signed')
    ->name('appointments.checkin');

require __DIR__ . '/auth.php';