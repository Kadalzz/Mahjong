<?php

return [

    

    'name' => env('APP_NAME', 'Laravel'),

    

    'env' => env('APP_ENV', 'production'),

    

    'debug' => (bool) env('APP_DEBUG', false),

    

    'url' => env('APP_URL', 'http://localhost'),

    'points_domain' => env('POINTS_DOMAIN'),

    'points_staff_whatsapp' => preg_replace('/^0/', '62', preg_replace('/\D+/', '', (string) env('POINTS_STAFF_WHATSAPP', ''))),

    

    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
