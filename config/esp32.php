<?php

return [
    // Local bridge process (esp32-bridge/bridge.php) that holds the
    // persistent WebSocket connection to the ESP32 Master and relays
    // commands from Laravel to it. Loopback only - not exposed on the LAN.
    'bridge_host' => env('ESP32_BRIDGE_HOST', '127.0.0.1'),
    'bridge_port' => env('ESP32_BRIDGE_CONTROL_PORT', 8090),

    // Shared secret between Laravel and the bridge process (not the ESP32
    // itself). Must match `secret` in esp32-bridge/config.php.
    'bridge_secret' => env('ESP32_BRIDGE_SECRET', ''),

    'timeout' => env('ESP32_TIMEOUT', 5),
];
