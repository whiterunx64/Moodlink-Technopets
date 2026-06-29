<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alert the admin inbox when a scheduled session has started but the student has not checked in.
Schedule::command('appointments:notify-awaiting')
    ->everyMinute()
    ->withoutOverlapping();

// Mark scheduled sessions as missed once the student is past the check-in grace period.
Schedule::command('appointments:reject-no-shows')
    ->everyMinute()
    ->withoutOverlapping();

// Confirm to students that their session is complete once its scheduled end time has passed.
Schedule::command('appointments:notify-completed')
    ->everyMinute()
    ->withoutOverlapping();

// Email students a reminder about an hour before their scheduled session.
Schedule::command('appointments:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('students:sync-at-risk')
    ->everyMinute();
