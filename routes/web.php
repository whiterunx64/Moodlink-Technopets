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


Route::get('/preview/appointment-reminder', function () {

    $student = (object) [
        'first_name' => 'Juan',
    ];

    $appointment = (object) [
        'display_date' => 'June 30, 2026',
        'display_time' => '10:00 AM',
    ];

    return view('mail.appointment-reminder-mail', [
        'student' => $student,
        'appointment' => $appointment,
        'logoData' => null,
    ]);

})->name('preview.appointment.reminder');

Route::get('/preview/approve-appointment-mail', function () {
    abort_unless(app()->environment('local'), 403);

    $student = new App\Models\Student([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $appointment = new App\Models\Appointment([
        'context' => 'Academic stress',
        'datetime' => now()->addDays(3)->setTime(10, 30),
    ]);
    $appointment->id = 42;
    $appointment->setRelation('student', $student);

    return new App\Mail\ApproveAppointmentMailable($student, $appointment);
})->name('preview.approve-appointment-mail');

// Local-only preview of the rejected-appointment email. No DB writes, no mail sent.
Route::get('/preview/reject-appointment-mail', function () {
    abort_unless(app()->environment('local'), 403);

    $student = new App\Models\Student([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $appointment = new App\Models\Appointment([
        'context' => 'Academic stress',
        'datetime' => now()->addDays(3)->setTime(10, 30),
    ]);
    $appointment->id = 42;
    $appointment->setRelation('student', $student);

    return new App\Mail\RejectAppointmentMailable($student, $appointment);
})->name('preview.reject-appointment-mail');

// Local-only preview of the student-account credentials email. No DB writes, no mail sent.
Route::get('/preview/student-account-password', function () {
    abort_unless(app()->environment('local'), 403);

    $student = new App\Models\Student([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    return new App\Mail\InitialPasswordMailable(
        $student,
        'maria.santos@student.feu.edu.ph',
        'Tmp-9f2K7xQ4',
    );
})->name('preview.student-account-password');

Route::get('/preview/account-deletion', function () {
    abort_unless(app()->environment('local'), 403);

    $student = new App\Models\Student([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    return new App\Mail\AccountDeletionMailable($student);
})->name('preview.account-deletion');

Route::get('/test-password', function () {
    $svc = app(App\Services\UserAccount\PasswordGenerator::class);
    $m = new ReflectionMethod($svc, 'generateInitialPassword');
    $m->setAccessible(true);

    return collect(range(1, 20))->map(fn() => $m->invoke($svc));
});

Route::middleware(['auth', 'supabase.verify-token', 'supabase.require-admin-access', 'supabase.single-session'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

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
        filled($cronKey) && hash_equals($cronKey, (string) $request->header('X-Cron-Key')),
        403
    );
    Artisan::call('schedule:run');
    return response()
        ->json(['status' => 'ok', 'ran_at' => now()->toIso8601String()])
        ->header('Cache-Control', 'no-store');
})->middleware('throttle:10,1')->name('cron.run');

Route::get('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])
    ->middleware('signed')
    ->name('appointments.checkin');

require __DIR__ . '/auth.php';