<?php

return [
    'connection' => 'pharma_marketing',
    'media_disk' => env('PHARMA_MEDIA_DISK', 'public'),

    // External messaging drivers
    'whatsapp_driver' => env('WHATSAPP_DRIVER', 'twilio'),  // twilio|vonage|meta
    'whatsapp_api_key' => env('WHATSAPP_API_KEY'),
    'whatsapp_from' => env('WHATSAPP_FROM'),
    'sms_driver' => env('SMS_DRIVER', 'twilio'),
    'sms_api_key' => env('SMS_API_KEY'),
    'sms_from' => env('SMS_FROM'),

    // Visit rules
    'min_visit_duration_minutes' => env('PM_MIN_VISIT_DURATION', 5),
    'gps_accuracy_threshold_meters' => env('PM_GPS_ACCURACY', 100),

    // Plan rules
    'plan_submission_cutoff_days' => env('PM_PLAN_CUTOFF', 2),  // days before week start
    'max_visits_per_day' => env('PM_MAX_DAILY_VISITS', 12),
];
