<?php
return [
    'type' => 'smtp',
    'smtp' => [
        'server' => env('SMTP_SERVER', ''),
        'port' => env('SMTP_PORT', ''),
        'user' => env('SMTP_USER', ''),
        'username' => env('SMTP_USERNAME', 'LineIm'),
        'password' => env('SMTP_PASSWORD', ''),
    ]
];