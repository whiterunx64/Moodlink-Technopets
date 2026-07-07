<?php

declare(strict_types=1);

/*
 * Authentication messages.
 *
 * `failed` is intentionally neutral: it never reveals whether the email exists
 * or which field was wrong, so it can't be used to enumerate accounts — while
 * still reading like a helpful nudge rather than a database error.
 */

return [

    'failed' => 'The email or password you entered is incorrect. Please try again.',

    'password' => 'That password is incorrect. Please try again.',

    'throttle' => "You've made too many sign-in attempts in a short period. Please wait :minutes minute(s) before trying again.",

    'locked' => "Your email account has been locked after the system detected multiple unsuccessful sign-in attempts. It will unlock at :time. Contact your system administrator if you need help.",
];
