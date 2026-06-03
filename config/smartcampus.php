<?php

return [
    'allowed_ip_ranges' => array_filter(array_map('trim', explode(',', env(
        'SMARTCAMPUS_ALLOWED_IP_RANGES',
        '127.0.0.1/32,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'
    )))),

    'qr_expires_minutes' => (int) env('SMARTCAMPUS_QR_EXPIRES_MINUTES', 10),

    'attendance_windows' => [
        'qr_phase_minutes' => (int) env('SMARTCAMPUS_QR_PHASE_MINUTES', 10),
        'normal_late_until_minutes' => (int) env('SMARTCAMPUS_NORMAL_LATE_UNTIL_MINUTES', 30),
        'severe_late_until_minutes' => (int) env('SMARTCAMPUS_SEVERE_LATE_UNTIL_MINUTES', 60),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
    ],
];
