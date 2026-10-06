# Hóng Zhōng Mahjong

Sistem reservasi meja Mahjong online — pemilihan meja, booking per jam, pembayaran online, waiting list, dan dashboard admin untuk kelola meja, harga, dan laporan.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3)
- **Database**: SQLite (lokal) / PostgreSQL (produksi)
- **Frontend**: Blade, Bootstrap 5
- **Pembayaran**: Xendit (invoice/checkout)
- **Hardware**: ESP32 (aktivasi meja otomatis) — lihat `esp32/README.md`

## Setup Lokal

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed
npm run build

php artisan serve
```

Untuk menjalankan queue worker dan scheduler (dibutuhkan untuk auto-off meja, jeda, dan pembersihan booking yang belum dibayar):

```bash
php artisan queue:work
php artisan schedule:work
```

## Environment Variables

Lihat `.env.example` untuk daftar lengkap. Yang wajib diisi sebelum pakai pembayaran/ESP32:

- `XENDIT_SECRET_KEY`, `XENDIT_CALLBACK_TOKEN` — dari dashboard Xendit
- `ESP32_BRIDGE_SECRET` — harus sama dengan `esp32-bridge/config.php`

## Deployment

- `Dockerfile` + `docker-entrypoint.sh` — build & jalankan sebagai container (migrasi, queue worker, scheduler, web server dalam satu proses)
- `render.yaml` — Blueprint untuk deploy ke Render

## Struktur Proyek

- `app/` — controller, model, service, job
- `esp32/` — firmware Master & Client (Arduino/ESP32)
- `esp32-bridge/` — proses WebSocket bridge yang menjembatani Laravel ke ESP32 Master (project PHP terpisah, lihat `esp32/README.md`)
