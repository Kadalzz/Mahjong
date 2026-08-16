# ESP32 — Hóng Zhōng Mahjong

Firmware + bridge untuk mengaktifkan meja secara otomatis saat pembayaran booking berhasil.

```
Laravel (server)  --TCP lokal-->  esp32-bridge/bridge.php  --WebSocket-->  ESP32 Master  --ESP-NOW-->  ESP32 Client (per meja)
```

- **`esp32-bridge/bridge.php`**: proses PHP terpisah (bukan bagian dari aplikasi Laravel) yang jadi WebSocket **server**. ESP32 Master konek ke sini sebagai client. Laravel mengirim perintah ke proses ini lewat koneksi TCP lokal (`127.0.0.1`, tidak diekspos ke jaringan).
- **`esp32/master/master.ino`**: satu perangkat, terhubung ke WiFi lokal, konek sebagai WebSocket client ke bridge, lalu meneruskan perintah ke Client yang dituju lewat ESP-NOW.
- **`esp32/client/client.ino`**: satu per meja, tidak perlu WiFi (cukup ESP-NOW), nyalakan/matikan relay saat dapat perintah dari Master, dan kirim ACK balik.

Auto-off (mematikan meja saat waktu booking habis) dijadwalkan oleh Laravel sendiri (bukan oleh firmware) - lihat `App\Jobs\DeactivateTableDevice`.

## Menjalankan bridge

`esp32-bridge/` adalah project PHP terpisah dengan `composer.json` sendiri (supaya dependency-nya - ReactPHP - tidak bentrok dengan dependency Laravel).

```
cd esp32-bridge
composer install
php bridge.php
```

Proses ini harus tetap berjalan (bersamaan dengan `php artisan serve`) supaya Laravel bisa mengirim perintah ke ESP32 Master. Salin `esp32-bridge/config.example.php` ke `esp32-bridge/config.php` (file ini di-gitignore) lalu isi:

```php
return [
    'ws_port' => 81,            // port yang didengarkan untuk ESP32 Master
    'control_port' => 8090,     // port lokal untuk perintah dari Laravel
    'secret' => '...',          // harus sama dengan ESP32_BRIDGE_SECRET di .env Laravel
];
```

## Library yang perlu diinstall (Arduino IDE → Library Manager)

- `WebSockets` oleh Markus Sattler (Links2004) — untuk `master.ino`
- (Client tidak butuh library tambahan, `esp_now.h`/`WiFi.h` sudah bawaan ESP32 core)

## Langkah setup

1. **Flash `client.ino`** ke tiap ESP32 meja. Sebelum flash, ganti `MEJA_ID` dan `NAMA_MEJA` supaya unik per meja. Buka Serial Monitor (115200 baud) — akan tercetak MAC address perangkat itu, catat untuk langkah berikutnya.
2. **Isi `master.ino`**: ganti `ssid`, `password`, `websocket_server` (IP komputer yang menjalankan `bridge.php`), lalu tambahkan MAC tiap Client ke array `clientMAC[]` sesuai urutan `meja_id`-nya (perbesar `NUM_MEJA` kalau menambah meja). Lalu flash ke ESP32 Master.
3. Jalankan `esp32-bridge/bridge.php` di komputer yang sama dengan Laravel.
4. Di Laravel `.env`, isi:
   ```
   ESP32_BRIDGE_HOST=127.0.0.1
   ESP32_BRIDGE_CONTROL_PORT=8090
   ESP32_BRIDGE_SECRET=<sama seperti di esp32-bridge/config.php>
   ```
5. Di dashboard admin → **Kelola Meja**, isi field **ID Meja ESP32** untuk tiap meja dengan angka yang sama seperti index di `clientMAC[]` Master (1, 2, dst).

## Format perintah (kontrak Laravel ↔ bridge ↔ Master)

Laravel mengirim JSON satu baris ke bridge lewat TCP lokal:

```json
{"secret": "<shared secret>", "command": "M1,ON"}
```

Bridge meneruskan `command` apa adanya ke ESP32 Master lewat WebSocket. Format command yang didukung Master: `M<id>,ON`, `M<id>,OFF`, `ALL,ON`, `ALL,OFF`, `STATUS`.

Master lalu meneruskan perintah ke Client yang sesuai lewat ESP-NOW, dan Client membalas dengan ACK yang diteruskan Master kembali ke bridge (untuk sekarang cuma dicatat di log, belum ditampilkan di dashboard).

## Kenapa channel WiFi harus sama?

ESP-NOW cuma bisa komunikasi antar perangkat yang berada di channel WiFi yang sama. Master otomatis pakai channel dari router yang ia sambungkan, sedangkan Client (yang tidak konek ke access point) harus di-set manual ke channel yang sama (`WIFI_CHANNEL` di `client.ino`). Kalau Client tidak menyala/menerima perintah sama sekali, ini penyebab paling umum — cek ulang channel-nya lewat Serial Monitor Master.

## Catatan keamanan

- ESP-NOW dikirim **tanpa enkripsi** (`peerInfo.encrypt = false`). Untuk pemakaian jangka panjang, pertimbangkan aktifkan enkripsi ESP-NOW.
- Koneksi WebSocket antara ESP32 Master dan bridge **tidak diautentikasi** — siapa pun yang terhubung ke WiFi toko dan tahu port 81 bisa konek dan mengirim perintah langsung ke Master. Ini masih tahap prototype; untuk produksi sebaiknya tambahkan pengecekan token di level firmware Master juga, bukan cuma di kanal Laravel↔bridge.
- Kanal Laravel↔bridge (`ESP32_BRIDGE_SECRET`) hanya melindungi loopback lokal (`127.0.0.1`), bukan komunikasi ke ESP32.
