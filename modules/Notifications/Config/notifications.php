<?php

return [
    'connection' => 'notifications',
    'channels' => ['push'],
    'push' => [
        'driver' => env('PUSH_DRIVER', 'fcm'),   // fcm|apns|web-push
        'fcm_key' => env('FCM_SERVER_KEY'),
        'apns_key' => env('APNS_KEY'),
    ],
    'batch_size' => 500,
    'retry_attempts' => 3,

    'notifications' => ['driver' => 'pgsql', 'host' => env('DB_HOST'), 'port' => env('DB_PORT', '5432'), 'database' => env('DB_DATABASE'), 'username' => env('DB_USERNAME'), 'password' => env('DB_PASSWORD', ''), 'charset' => 'utf8', 'prefix' => '', 'prefix_indexes' => true, 'search_path' => 'notifications', 'sslmode' => 'prefer'],
];
