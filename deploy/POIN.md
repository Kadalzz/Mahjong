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

Pastikan dua baris ini ada:

```
POINTS_DOMAIN=poin.hzmahjong.tech
POINTS_STAFF_WHATSAPP=08xxxxxxxxxx
```

Nomor WhatsApp boleh diawali `0` atau `62`; spasi dan tanda `-` diabaikan. Kalau kosong, tombol "Hubungi Staf Venue" di halaman Bantuan disembunyikan.

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

## 4. Cek setelah deploy

```bash
curl -I https://poin.hzmahjong.tech
curl -I https://poin.hzmahjong.tech/leaderboard
cat /etc/php/*/fpm/conf.d/99-hzmahjong.ini
```

Dua `curl` harus `HTTP/2 200`. Lalu dari HP: daftar akun, scan QR meja dari Dashboard → Meja → QR, mainkan satu ronde, dan unggah foto profil.
