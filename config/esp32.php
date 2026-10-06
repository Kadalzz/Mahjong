<?php

return [
    
    
    
    'bridge_host' => env('ESP32_BRIDGE_HOST', '127.0.0.1'),
    'bridge_port' => env('ESP32_BRIDGE_CONTROL_PORT', 8090),

    
    
    
    
    
    'bridge_mode'     => env('ESP32_BRIDGE_MODE', 'tcp'),
    'bridge_http_url' => env('ESP32_BRIDGE_HTTP_URL', ''),

    
    
    'bridge_secret' => env('ESP32_BRIDGE_SECRET', ''),

    'timeout' => env('ESP32_TIMEOUT', 5),
];
