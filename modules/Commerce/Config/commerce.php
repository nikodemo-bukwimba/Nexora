<?php

return [
    'connection'   => 'commerce',
    'order_prefix' => env('COMMERCE_ORDER_PREFIX', 'ORD'),
    'auto_confirm_on_payment' => env('COMMERCE_AUTO_CONFIRM', true),
    'reservation_ttl_minutes' => env('COMMERCE_RESERVATION_TTL', 30),
];
