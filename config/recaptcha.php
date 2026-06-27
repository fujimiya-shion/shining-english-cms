<?php

return [
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    'register_action' => env('RECAPTCHA_REGISTER_ACTION', 'register'),
    'contact_action' => env('RECAPTCHA_CONTACT_ACTION', 'contact'),
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    'timeout_seconds' => 8,
];
