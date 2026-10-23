<?php

return [
    'enabled'       => (bool) env('AYO_ENABLED', false),
    'base_url'      => rtrim((string) env('AYO_BASE_URL', 'https://sandbox.ayodev.xyz/api/v2/third-party'), '/'),
    'venue_code'    => env('AYO_VENUE_CODE', ''),
    'token'         => env('AYO_API_TOKEN', ''),
    'private_key'   => env('AYO_PRIVATE_KEY', ''),
    'webhook_key'   => env('AYO_WEBHOOK_KEY', ''),
    'timeout'       => (int) env('AYO_TIMEOUT', 8),
    'sync_days_back'  => 1,
    'sync_days_ahead' => 14,
];
