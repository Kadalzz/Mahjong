# Sistem Poin — poin.hzmahjong.tech

Subdomain poin dilayani oleh aplikasi dan blok nginx yang sama dengan hzmahjong.tech. Rute poin aktif hanya untuk host yang sama persis dengan `POINTS_DOMAIN`.

## 1. DNS (sekali saja, di panel domain)

Tambahkan record:

| Tipe | Nama | Nilai | TTL |
|------|------|-------|-----|
| A | `poin` | `187.127.223.136` | 3600 |

Cek dari VPS sebelum deploy:

```bash
getent ahostsv4 poin.hzmahjong.tech
```

Baris pertama harus `187.127.223.136`.

## 2. Env server (`/root/hzmahjong.env`)

Pastikan baris ini ada:

```
MAIN_DOMAIN=hzmahjong.tech
POINTS_DOMAIN=poin.hzmahjong.tech
POINTS_STAFF_WHATSAPP=08xxxxxxxxxx
```

`MAIN_DOMAIN` membatasi booking, dashboard, login staf, dan webhook DOKU/AYO ke `hzmahjong.tech` dan `www.hzmahjong.tech`; host lain (termasuk subdomain poin dan akses lewat IP) mendapat 404. URL webhook yang didaftarkan di DOKU dan AYO harus memakai salah satu dari dua host itu. Kalau baris ini kosong, tidak ada pembatasan.

Nomor WhatsApp boleh diawali `0` atau `62`; spasi dan tanda `-` diabaikan. Kalau kosong, tombol "Hubungi Staf Venue" di halaman Bantuan disembunyikan.

Jangan isi `POINTS_DEV_PAGES=true` di server. Halaman `/dev/tokens` hanya untuk laptop pengembang dan tetap 404 selama `APP_ENV` bukan `local`.

## 3. Deploy

```bash
cd /var/www/hzmahjong && git pull --ff-only && sudo bash deploy/deploy.sh
```

Yang dilakukan untuk sistem poin:

- `server_name` nginx ditambah `POINTS_DOMAIN`.
- Batas unggah: nginx `client_max_body_size 8m`, PHP-FPM `upload_max_filesize 6M` dan `post_max_size 8M` lewat `/etc/php/<versi>/fpm/conf.d/99-hzmahjong.ini`.
- Sertifikat `hzmahjong.tech` diperluas dengan `poin.hzmahjong.tech` hanya kalau DNS-nya sudah mengarah ke server. Kalau belum, muncul `PERINGATAN: DNS ...` dan sertifikat lama tetap dipakai; jalankan ulang deploy setelah DNS aktif.
- Migrasi tabel pemain, game, ronde, dan kolom foto profil.
- `storage:link` untuk foto profil di `storage/app/public/avatars`.
- Paket `php-gd` untuk mengolah ulang foto profil di server: sisi terpanjang maksimal 512 px, metadata EXIF (termasuk lokasi GPS) dibuang, dan gambar di atas 4096 px ditolak.
- Scheduler `poin:close-idle-games` setiap 5 menit menutup game yang tidak ada aktivitas selama `POINTS_IDLE_PLAYING_MINUTES` (bawaan 180) dan lobby yang belum Start selama `POINTS_IDLE_LOBBY_MINUTES` (bawaan 45). Ronde yang sudah selesai tetap dihitung.

## 4. Cek setelah deploy

```bash
curl -I https://poin.hzmahjong.tech
curl -I https://poin.hzmahjong.tech/leaderboard
cat /etc/php/*/fpm/conf.d/99-hzmahjong.ini
```

Dua `curl` harus `HTTP/2 200`. Lalu dari HP: daftar akun, scan QR meja dari Dashboard → Meja → QR, mainkan satu ronde, dan unggah foto profil.
