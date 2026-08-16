<?php

return [
    // Port yang didengarkan untuk koneksi WebSocket dari ESP32 Master.
    // Harus sama dengan `websocket_port` di firmware master.ino.
    'ws_port' => 81,

    // Port lokal (127.0.0.1 saja, tidak diekspos ke jaringan) yang dipakai
    // Laravel untuk mengirim perintah ke proses bridge ini.
    // Harus sama dengan ESP32_BRIDGE_CONTROL_PORT di .env Laravel.
    'control_port' => 8090,

    // Kunci rahasia antara Laravel dan proses bridge ini (bukan dengan ESP32).
    // Harus sama persis dengan ESP32_BRIDGE_SECRET di .env Laravel.
    'secret' => 'ganti-dengan-secret-acak',
];
