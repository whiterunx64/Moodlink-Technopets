<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PostManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => redirect()->route('login'));

Route::get(config('supabase-auth.monitoring.health_checks.endpoint'), [HealthController::class, 'check'])
    ->name('health');

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
Route::get('/test-password', function () {
    $svc = app(App\Services\StudentAccountService::class);
    $m = new ReflectionMethod($svc, 'generateInitialPassword');
    $m->setAccessible(true);

    return collect(range(1, 20))->map(fn() => $m->invoke($svc));
});

Route::middleware(['auth', 'supabase.verify-token', 'supabase.require-admin-access', 'supabase.single-session'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/student-accounts', [UserAccountController::class, 'index'])
        ->name('student-accounts.index');
    Route::post('/student-accounts/{student}/registration', [UserAccountController::class, 'createRegistrationAccount'])
        ->name('student-accounts.registration.store');
    Route::patch('/student-accounts/{student}/registration/accept', [UserAccountController::class, 'acceptStudentRegistration'])
        ->name('student-accounts.registration.accept');
    Route::delete('/student-accounts/{student}/registration', [UserAccountController::class, 'destroyStudentRegistration'])
        ->name('student-accounts.registration.destroy');
    Route::patch('/student-accounts/{authUserId}/restrict-access', [UserAccountController::class, 'restrictStudentAccountAccess'])
        ->name('student-accounts.restrict-access');
    Route::patch('/student-accounts/{authUserId}/restore-access', [UserAccountController::class, 'restoreStudentAccountAccess'])
        ->name('student-accounts.restore-access');

    Route::get('/post-management', [PostManagementController::class, 'index'])
        ->name('post-management.index');
    Route::patch('/post-management/{post}/flagPost', [PostManagementController::class, 'flagPost'])
        ->name('post-management.flagPost');
    Route::patch('/post-management/{post}/unflagPost', [PostManagementController::class, 'unflagPost'])
        ->name('post-management.unflagPost');

    Route::get('/summary-reports', [SummaryReportController::class, 'index'])
        ->name('summary-reports.index');
    Route::get('/summary-reports/section/{section}', [SummaryReportController::class, 'showSectionAggregatedReport'])
        ->name('summary-reports.section-aggregated-report');
    Route::get('/summary-reports/students/{studentId}', [SummaryReportController::class, 'showStudentReport'])
        ->name('summary-reports.student-mood-report');
    Route::post('/summary-reports/{student}/consult', [SummaryReportController::class, 'consult'])
        ->name('summary-reports.consult');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::patch('/appointments/{appointment}/approve', [AppointmentController::class, 'approve'])
        ->name('appointments.approve');
    Route::patch('/appointments/{appointment}/reject', [AppointmentController::class, 'reject'])
        ->name('appointments.reject');
    Route::patch('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])
        ->name('appointments.complete');
    Route::post('/appointments/schedules', [AppointmentController::class, 'storeSchedule'])
        ->name('appointments.schedules.store');
    Route::delete('/appointments/schedules/{schedule}', [AppointmentController::class, 'destroySchedule'])
        ->name('appointments.schedules.destroy');

    Route::get('/profile', [ProfileController::class, 'settings'])
        ->name('profile.settings');
    Route::patch('/profile/change-metadata', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::put('/profile/change-password', [ProfileController::class, 'updatePassword'])
        ->middleware('supabase.revalidate')
        ->name('profile.password.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])
        ->name('profile.preferences.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->name('profile.avatar.update');
    Route::delete('/profile/account', [ProfileController::class, 'destroy'])
        ->middleware('supabase.revalidate')
        ->name('profile.account.delete');
});

require __DIR__ . '/auth.php';