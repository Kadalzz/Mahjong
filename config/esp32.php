<?php

return [
    // Local bridge process (esp32-bridge/bridge.php) that holds the
    // persistent WebSocket connection to the ESP32 Master and relays
    // commands from Laravel to it. Loopback only - not exposed on the LAN.
    'bridge_host' => env('ESP32_BRIDGE_HOST', '127.0.0.1'),
    'bridge_port' => env('ESP32_BRIDGE_CONTROL_PORT', 8090),

    // 'tcp' (default) connects straight to bridge_host:bridge_port - only
    // works when Laravel and the bridge process run on the same network.
    // 'http' posts to bridge_http_url instead (e.g. a Cloudflare Tunnel
    // URL pointing at esp32-bridge/http_relay.php) - use this when Laravel
    // runs on a cloud host (Render) that can't reach the bridge directly.
    'bridge_mode'     => env('ESP32_BRIDGE_MODE', 'tcp'),
    'bridge_http_url' => env('ESP32_BRIDGE_HTTP_URL', ''),

    // Shared secret between Laravel and the bridge process (not the ESP32
    // itself). Must match `secret` in esp32-bridge/config.php.
    'bridge_secret' => env('ESP32_BRIDGE_SECRET', ''),

    'timeout' => env('ESP32_TIMEOUT', 5),
];
