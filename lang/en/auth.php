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

    'failed' => "We couldn't sign you in. Please double-check your email and password, then try again.",

    'password' => 'That password is incorrect. Please try again.',

    'throttle' => "For your security, we've temporarily paused sign-in after several unsuccessful attempts. Please wait :minutes minute(s) before trying again.",

    'locked' => "Your account has been temporarily locked to protect it after repeated failed sign-in attempts. It will automatically unlock at :time. If you need access before then, please contact your system administrator so they can verify your account and investigate any issues.",
];
