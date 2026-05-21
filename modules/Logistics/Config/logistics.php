<?php

return [
    'connection'  => 'logistics',
    'media_disk'  => env('LOGISTICS_MEDIA_DISK', 'public'),

    // Cost calculation
    'default_currency'       => env('LOGISTICS_CURRENCY', 'TZS'),
    'weight_unit'            => 'kg',

    // Proof of delivery
    'pod_photo_required'     => env('LOGISTICS_POD_PHOTO', false),
    'pod_signature_required' => env('LOGISTICS_POD_SIGNATURE', false),
    'pod_code_required'      => env('LOGISTICS_POD_CODE', false),

    // Third-party couriers
    'couriers' => [
        'dhl'  => ['api_url' => 'https://api.dhl.com',  'version' => 'v2'],
        'g4s'  => ['api_url' => 'https://api.g4s.co.ke'],
        'sendy' => ['api_url' => 'https://api.sendyit.com/v1'],
    ],
];
