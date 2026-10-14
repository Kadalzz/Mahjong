<?php

return [
    'client_id' => env('DOKU_CLIENT_ID', ''),
    'secret_key' => env('DOKU_SECRET_KEY', ''),
    'is_production' => env('DOKU_IS_PRODUCTION', false),
    'payment_due_minutes' => env('DOKU_PAYMENT_DUE_MINUTES', 5),
    'payment_methods' => array_values(array_filter(array_map('trim', explode(',', env('DOKU_PAYMENT_METHODS', 'QRIS'))))),
];
