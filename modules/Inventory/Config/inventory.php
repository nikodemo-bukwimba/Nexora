<?php

return [
    'connection'     => 'inventory',
    'migration_path' => __DIR__ . '/../Database/Migrations',

    'alerts' => [
        'default_low_stock_threshold'  => env('INVENTORY_LOW_STOCK_THRESHOLD', 10),
        'default_expiry_warning_days'  => env('INVENTORY_EXPIRY_WARNING_DAYS', 30),
    ],
];
