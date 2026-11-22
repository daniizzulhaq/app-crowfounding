<?php

return [
    'brand' => 'Alkiroma Amanah',

    'order_prefix' => 'AKA',

    'donation' => [
        'min_amount' => 10_000,
        'max_amount' => 100_000_000,
        'expiry_minutes' => (int) env('DONATION_EXPIRY_MINUTES', 60),
        'quick_amounts' => [10_000, 25_000, 50_000, 100_000],
        'form_amounts' => [25_000, 50_000, 100_000, 250_000, 500_000, 1_000_000],
    ],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'session_minutes' => 30,
    ],

    'operational_fee_max_percent' => 10,

    'home_cache_seconds' => 120,
];
