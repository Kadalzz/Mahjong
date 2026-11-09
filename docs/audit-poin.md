# Audit Sistem Poin Chicken Hand

Urutan audit: 1, 4, 5, 3, 2, 6, 7, 0. Tingkat: KRITIS (keamanan, data, poin salah), SEDANG (fungsi rusak atau melanggar aturan), RENDAH (tampilan, teks, kerapian).

## Tahap 1 — Akun pemain

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 1.1 | Karakter tersembunyi (zero-width, soft hyphen, kontrol, bidi) di tengah nickname lolos, sehingga "Ri​na" terdaftar sebagai nama berbeda dari "Rina" dan bisa menyamar. | SEDANG | `test_nickname_with_invisible_characters_is_rejected` gagal sebelum perbaikan (200, bukan 422). Aturan baru `regex:/^\P{C}+$/u` di `Player::NICKNAME_RULES`, berlaku untuk daftar dan ubah username. | Diperbaiki |
| 1.2 | Guard `player` dan tabel `players` terpisah dari `users`. | — | `config/auth.php` guard `player`, `test_player_can_register_without_touching_the_staff_table`. | Sesuai |
| 1.3 | Pesan login sama untuk akun tidak ada dan sandi salah. | — | `test_login_error_does_not_reveal_whether_the_account_exists` (baru). | Sesuai |
| 1.4 | Session ID baru setelah login; logout mengganti session dan token CSRF. | — | `test_login_starts_a_new_session_id`, `test_logout_ends_the_session_and_the_old_csrf_token` (baru). | Sesuai |
| 1.5 | Semua route poin yang mengubah data berada di grup `web` (CSRF) dan, selain daftar/masuk, dijaga `AuthenticatePlayer`. | — | `test_every_points_route_that_changes_data_is_protected` (baru). | Sesuai |
| 1.6 | Throttle login (5/menit per nama+IP, 60/menit per IP) dan daftar (20/menit per IP). | — | `test_login_is_throttled_per_ip`, `test_registration_is_throttled_per_ip`. | Sesuai |
| 1.7 | Reset sandi oleh admin hanya untuk `role:admin`, tercatat di activity log, dan sesi pemain lama berakhir. | — | `routes/web.php:106-108`, `test_admin_can_reset_a_player_password_and_old_sessions_end`, `test_only_admin_can_open_the_player_reset_pages`. | Sesuai |
| 1.8 | Route situs utama (booking, `/login` staf, dashboard) juga melayani host subdomain poin karena tidak dibatasi domain. Tidak ada kebocoran (sesi per host), tapi `poin.hzmahjong.tech/dashboard` menampilkan login staf. | RENDAH | `routes/web.php` tanpa `Route::domain`. | Diperbaiki (K4, `ff55115`) |
| 1.9 | Waktu respons login berbeda antara akun ada dan tidak ada (hash hanya dihitung bila akun ada). Keberadaan akun memang sudah terlihat dari pesan "sudah dipakai" saat daftar. | RENDAH | `Auth::guard('player')->attempt` tanpa timebox. | Ditunda |
| 1.10 | Nama yang mirip secara visual (huruf Kiril "а" vs Latin "a", spasi ganda) dianggap berbeda. | RENDAH | `Player::nicknameKey` hanya `mb_strtolower(trim())`. | Diperbaiki sebagian (K5, `45edaa6`) |
| 1.11 | Pencarian pemain di dashboard tidak meng-escape `%`/`_` (tetap memakai binding, hanya hasil pencarian lebih luas). | RENDAH | `Dashboard/PlayerController.php:17`. | Diperbaiki (K7, `0ab1140`) |

Commit: lihat `git log` dengan pesan "Reject hidden characters in player nicknames…".

## Tahap 4 — Hasil dan poin

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 4.1 | "Batalkan" tidak membawa nomor ronde. Klik dua kali, atau membatalkan lagi dari halaman hasil, ikut menghapus ronde sebelumnya kalau pencatatnya sama, sehingga poin ronde yang sah hilang. | KRITIS | `test_undoing_twice_does_not_remove_the_round_before` gagal sebelum perbaikan (ronde 1 ikut terhapus). Perbaikan: undo wajib `ronde`, `RoundService::canUndo` menolak ronde ≤ `games.undo_floor` (migrasi `2026_11_05_000000_add_undo_floor_to_games_table`), form menyertakan `ronde`. | Diperbaiki |
| 4.2 | Ronde yang dimasukkan ulang setelah dibatalkan tetap bisa dibatalkan. | — | `test_a_round_recorded_again_after_undo_can_be_undone` (baru). | Sesuai |
| 4.3 | Undo tanpa nomor ronde ditolak dan tombol hilang setelah dipakai. | — | `test_undo_needs_the_round_number_and_the_button_hides_once_used` (baru). | Sesuai |
| 4.4 | PointCalculator murni (tanpa database) dan teruji untuk 16 kombinasi, total nol. | — | `tests/Unit/PointCalculatorTest.php` (`test_every_win_gives_the_agreed_points_and_sums_to_zero`, `test_there_are_sixteen_possible_results`). | Sesuai |
| 4.5 | Input ganda tidak membuat ronde ganda: kunci baris game, cek nomor ronde, indeks unik `(game_id, number)`. | — | `RoundService::record`, migrasi `2026_11_03_000003`, `test_a_round_is_only_recorded_once`. | Sesuai |
| 4.6 | Non-anggota game ditolak di semua endpoint ronde (menang, simpan, hasil, status, lanjut, batalkan, berhenti, ringkasan). | — | `test_only_seated_players_can_touch_the_round` (diperluas). | Sesuai |
| 4.7 | Berhenti di tengah game mempertahankan ronde selesai; ronde berjalan tidak tercatat. Tidak ada tombol keluar perorangan saat bermain. | — | `test_stopping_ends_the_session_for_everyone_and_keeps_finished_rounds`, `GameController::leave` hanya di lobby. | Sesuai |
| 4.8 | Foreign key `players` di `game_rounds` memakai `cascadeOnDelete`: kalau satu pemain dihapus langsung dari database, ronde yang ia menangkan beserta poin tiga pemain lain ikut terhapus. Belum ada fitur hapus pemain. | RENDAH | `database/migrations/2026_11_03_000003_create_game_rounds_table.php:15-17`. | Diperbaiki (K7, `0ab1140`) |
| 4.9 | Layar Menang, Kalah, Sesi Selesai, Leaderboard dan kondisinya. | — | Lihat daftar 66 layar di Tahap 7. | Lihat Tahap 7 |

Commit: "Stop a second undo from deleting the round before…".

## Tahap 5 — Profil

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 5.1 | Foto disimpan apa adanya tanpa encode ulang di server. Metadata EXIF (termasuk lokasi GPS dari kamera HP) ikut tersimpan dan bisa diunduh siapa pun yang melihat avatar. Normalnya aman karena browser meng-encode ulang lewat canvas (`resources/js/poin.js:113-131`), tapi tidak kalau `createImageBitmap` gagal atau request dikirim langsung. Perbaikan butuh paket `php-gd` (belum terpasang di server, `deploy/deploy.sh:25-27`). | SEDANG | `AvatarService::store` memakai `storeAs` pada file asli. | Diperbaiki (K2, `2b4b8cd`) |
| 5.2 | Tidak ada batas dimensi gambar, hanya ukuran file 5 MB. PNG 5 MB bisa berukuran puluhan ribu piksel dan membuat tab browser pemain lain crash saat avatar tampil di lobby atau leaderboard. | SEDANG | `ProfileController::updatePhoto` aturan `dimensions:min_width=1,min_height=1`. | Diperbaiki (K2, `2b4b8cd`) |
| 5.3 | Foto yang dihapus tetap tersimpan (untuk Urungkan) dan masih bisa dibuka lewat URL acak 40 karakter sampai pemain mengunggah foto baru. | RENDAH | `AvatarService::remove`. | Pertanyaan (bertabrakan dengan Urungkan, lihat K3) |
| 5.4 | Isi file diperiksa (mimes dari isi, mimetypes, `dimensions` membaca header gambar), nama file `.php` ditolak, nama tersimpan acak dengan ekstensi dari isi, gambar berisi kode tetap disimpan sebagai `.png` statis. | — | `test_only_small_jpg_or_png_files_are_accepted`, `test_a_photo_is_stored_under_a_random_name_and_replaces_the_old_one`, `test_an_image_carrying_code_is_kept_as_a_plain_image_file` (baru). nginx hanya meneruskan `\.php$` ke PHP dan mengirim `X-Content-Type-Options: nosniff`. | Sesuai |
| 5.5 | Foto lama dihapus saat diganti; foto yang dihapus lalu diganti foto baru ikut dihapus. | — | `test_a_new_photo_after_removal_deletes_the_removed_file`. | Sesuai |
| 5.6 | Ubah username mengikuti aturan nickname (termasuk karakter tersembunyi dari 1.1). | — | `test_username_must_be_free_and_valid`. | Sesuai |
| 5.7 | Ubah sandi meminta sandi lama, sesi ini tetap, sesi lain berakhir; throttle 10/menit. | — | `test_password_change_checks_the_old_password_and_the_repeat`, `test_password_change_keeps_this_session_and_ends_the_others`, `routes/poin.php:44`. | Sesuai |
| 5.8 | Semua aksi profil memakai pemain yang sedang login, tanpa ID dari request, jadi tidak ada jalur mengubah akun lain. | — | `ProfileController::player()`; tidak ada parameter route. | Sesuai |
| 5.9 | Unggah foto tidak di-throttle. Penyimpanan per pemain tetap terbatas (file lama dihapus). | RENDAH | `routes/poin.php:37`. | Diperbaiki (K2, `2b4b8cd`) |

Commit: "Cover image uploads that carry code…".

## Tahap 3 — Mulai main

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 3.1 | Dua request gabung yang datang bersamaan dari pemain yang sama ke dua meja berbeda bisa sama-sama lolos, karena transaksi hanya mengunci baris meja. Pemain lalu duduk di dua game aktif. | SEDANG | `LobbyService::join` sebelumnya hanya `MahjongTable::lockForUpdate`. Tidak bisa direproduksi lewat test: SQLite in-memory menjalankan satu koneksi, jadi dua transaksi tidak pernah berjalan bersamaan. Perbaikan: kunci baris pemain lebih dulu, lalu meja (urutan tetap, tanpa deadlock). | Diperbaiki |
| 3.2 | Game berstatus "playing" yang ditinggal semua pemain tidak pernah berakhir. Meja itu lalu menolak pemain baru ("sedang dipakai") sampai ada yang menekan Berhenti. | SEDANG | `LobbyService::tableStatus` → `playing`; tidak ada job atau scheduler yang menutup game lama (`routes/console.php`). Butuh keputusan batas waktu. | Diperbaiki (K1, `45ea69e`) |
| 3.3 | Kode meja 8 karakter acak A–Z0–9 (sekitar 2,8 × 10¹² kombinasi), unik per meja, tidak berurutan. | — | `MahjongTable.php:22`, `test_every_table_gets_a_unique_qr_code`, `test_table_codes_are_long_random_and_not_sequential` (baru). | Sesuai |
| 3.4 | Maksimal 4 pemain: kursi 1–4 dan indeks unik `(game_id, seat)` serta `(game_id, player_id)`. | — | migrasi `2026_11_03_000002:40-41`, `test_fifth_player_is_refused_when_the_table_is_full`. | Sesuai |
| 3.5 | Start ditolak sebelum keempat pemain Ready. | — | `Game::isReadyToStart`, `test_game_cannot_start_until_all_four_are_ready`. | Sesuai |
| 3.6 | Non-anggota tidak bisa membuka atau mengubah lobby/game (lobby, status, ready, start, keluar, game, status game). | — | `test_only_seated_players_can_open_a_game` (diperluas). | Sesuai |
| 3.7 | Polling 3 detik dengan rantai `setTimeout`, berhenti saat halaman ditutup; pemain tanpa polling 90 detik dikeluarkan dari lobby. | — | `resources/js/poin.js:277-337`, `test_inactive_players_lose_their_seat_after_ninety_seconds`. | Sesuai |
| 3.8 | Scan QR memakai paket `jsqr` (kamera di halaman), kondisi Izin kamera ditolak dan QR tidak dikenali tersedia. | — | `resources/js/poin-qr.js`, layar 158:1299 dan 158:1381 di Tahap 7. | Lihat Tahap 7 |

Commit: "Lock the player while joining a table…".

## Tahap 2 — Landing, pilih game, leaderboard publik

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 2.1 | Periode memotong tepat di tengah malam WIB: Minggu dimulai Senin 00:00:00, Bulan tanggal 1 00:00:00, Tahun 1 Januari 00:00:00; periode tak dikenal jatuh ke Tahun. | — | `LeaderboardService::periodStart`, `config/app.php:27` (`Asia/Jakarta`), `test_leaderboard_periods_cut_exactly_at_midnight_in_jakarta_time` (baru). | Sesuai |
| 2.2 | Leaderboard dan beranda hanya menampilkan nickname, tidak pernah email. | — | Query hanya memilih `nickname`, `avatar_path`; `test_leaderboard_never_shows_player_emails`. | Sesuai |
| 2.3 | Halaman publik (beranda, leaderboard, bantuan) terbuka tanpa login; input periode aneh tidak memengaruhi query. | — | `test_public_pages_open_without_login_and_ignore_an_unknown_period` (baru), `PageController` memakai `in_array(..., PERIODS, true)`. | Sesuai |
| 2.4 | Urutan poin, lalu jumlah menang, lalu nama; nomor peringkat berurutan (nilai seri tidak berbagi peringkat). | RENDAH | `LeaderboardService::ranking`, `test_public_leaderboard_needs_no_login_and_orders_by_points_then_wins`. Seri berbagi peringkat atau tidak adalah keputusan produk. | Diputuskan (K6, `335445e`) |
| 2.5 | Label "MUSIM" memakai tahun berjalan, tidak ditulis mati. | — | `PageController.php:50`. | Sesuai |
| 2.6 | Layar Landing, Pilih Game, MCR, Leaderboard publik dan kondisinya. | — | Lihat Tahap 7. | Lihat Tahap 7 |

Commit: "Pin leaderboard period edges…".

## Tahap 6 — Konfirmasi dan bantuan

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 6.1 | Dialog Keluar akun mengirim POST ber-CSRF ke `poin.logout`; Hapus foto ke `poin.profile.photo.remove`; Akhiri sesi ke `poin.round.stop`. Menekan "Ya, akhiri sesi" dua kali tetap aman (game selesai sekali, ronde tetap). | — | `components/poin/dialog-overlay.blade.php` (`@csrf`), `test_logout_and_photo_removal_ask_for_confirmation` dan `test_stopping_a_session_asks_for_confirmation_once_per_page` (diperluas dengan URL aksi dan klik ganda). | Sesuai |
| 6.2 | Bantuan memakai konstanta poin dari `PointCalculator`, tombol staf memakai `POINTS_STAFF_WHATSAPP` dan disembunyikan bila kosong, kembali ke profil bila login. | — | `test_help_page_explains_points_and_forgotten_passwords_to_guests`, `test_help_page_returns_players_to_their_profile_and_links_the_staff`. | Sesuai |
| 6.3 | Tanpa JavaScript, tombol Berhenti, Keluar dan Hapus Foto tidak melakukan apa pun karena dialog dibuka lewat JS dan tidak ada fallback. | RENDAH | `data-poin-dialog-open` di `partials/stop-session.blade.php`, `profile.blade.php`, `profile-photo.blade.php`. | Ditunda |

Commit: "Check dialog form targets…".

## Tahap 7 — Rilis

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 7.1 | `server_name` nginx berisi domain utama, `www`, dan `POINTS_DOMAIN` persis, tanpa wildcard; domain utama tetap pertama. | — | `deploy/nginx.conf.template:4`, `deploy/deploy.sh:65,123`. | Sesuai |
| 7.2 | Sertifikat diperluas ke subdomain poin hanya bila DNS-nya menunjuk ke IP server; kalau tidak, deploy tetap jalan dengan peringatan dan sertifikat lama dipakai. Pemeriksaan memakai `hostname -I`, jadi pada server di balik NAT (IP publik tidak ada di interface) subdomain tidak pernah ikut sertifikat. Server Hostinger saat ini belum diverifikasi. | RENDAH | `deploy/deploy.sh:160-168`. | Diperbaiki sebagian (K7, `0ab1140`) |
| 7.3 | `.env.example` memuat `POINTS_DOMAIN` dan `POINTS_DEV_PAGES=false`. | — | `.env.example:34-36`, `test_dev_pages_are_disabled_by_default`. | Sesuai |
| 7.4 | Catatan deploy dan record DNS `poin` → `187.127.223.136` ada; ditambah peringatan agar `POINTS_DEV_PAGES` tidak diisi `true` di server. | RENDAH | `deploy/POIN.md`. | Diperbaiki |
| 7.5 | Migrasi baru `2026_11_05_000000_add_undo_floor_to_games_table` (dari 4.1) ikut jalan otomatis lewat `php artisan migrate --force` di `deploy.sh`. Hanya menambah kolom dengan nilai bawaan 0. | — | `deploy/deploy.sh:113`. | Sesuai |
| 7.6 | 66 layar dibandingkan dengan render Figma per section (6 panggilan `get_screenshot`, semua berhasil) terhadap screenshot aplikasi 390 px dari kode terbaru. Hasil: 0 BELUM ADA, 0 BEDA yang tidak disepakati. | — | Tabel di bawah; pasangan Figma/aplikasi di folder scratchpad `r8/cmp_*.png`. | Sesuai |

Catatan pemotretan: layar dengan data contoh (angka poin, nama) berbeda isinya dari Figma karena data uji; tombol merah yang tampak lebih terang pada layar Gagal adalah efek hover desktop (`@media (hover: hover)`), tidak muncul di HP; layar yang dipotret dengan tinggi 896 px tampak bergeser ke bawah dibanding frame 844 px.

| No | Section | Layar | Status |
|---|---|---|---|
| 1 | Awal · Landing & Akun | `95:177` Landing page | SESUAI |
| 2 | Awal · Landing & Akun | `47:84` Daftar | SESUAI |
| 3 | Awal · Landing & Akun | `95:411` Masuk | BEDA (disepakati): label "EMAIL / NAMA PEMAIN" karena login menerima nickname atau email; tautan "Lupa sandi?" ke Bantuan. |
| 4 | Awal · Landing & Akun | `16:21` Pilih Game | BEDA (disepakati): ikon profil di kanan atas agar profil bisa dibuka dari Pilih Game. |
| 5 | Awal · Landing & Akun | `95:290` Coming Soon MCR | SESUAI |
| 6 | Awal · Landing & Akun | `157:1178` Landing page / Memuat | SESUAI |
| 7 | Awal · Landing & Akun | `157:1233` Landing page / Offline | SESUAI |
| 8 | Awal · Landing & Akun | `157:1307` Daftar / Mengirim | SESUAI |
| 9 | Awal · Landing & Akun | `157:1347` Daftar / Gagal terhubung | SESUAI |
| 10 | Awal · Landing & Akun | `157:1391` Masuk / Mengirim | BEDA (disepakati): label login dan tautan "Lupa sandi?" seperti 95:411. |
| 11 | Awal · Landing & Akun | `157:1422` Masuk / Gagal terhubung | BEDA (disepakati): label login dan tautan "Lupa sandi?" seperti 95:411. |
| 12 | Awal · Landing & Akun | `157:1457` Pilih Game / Memuat | BEDA (disepakati): ikon profil seperti 16:21. |
| 13 | Awal · Landing & Akun | `157:1512` Pilih Game / Gagal memuat | BEDA (disepakati): ikon profil seperti 16:21. |
| 14 | Awal · Landing & Akun | `201:2171` Leaderboard publik / Tahun Ini | SESUAI |
| 15 | Awal · Landing & Akun | `201:2298` Leaderboard publik / Bulan Ini | SESUAI |
| 16 | Awal · Landing & Akun | `201:2425` Leaderboard publik / Minggu Ini | SESUAI |
| 17 | Chicken Hand · Mulai Main | `47:117` Scan Meja | SESUAI |
| 18 | Chicken Hand · Mulai Main | `95:316` Meja Terdeteksi | SESUAI |
| 19 | Chicken Hand · Mulai Main | `16:53` Lobby (menunggu) | BEDA (disepakati): tombol Ready/Batal Ready di atas Start Game; kursi memakai nickname. |
| 20 | Chicken Hand · Mulai Main | `16:99` Lobby (siap) | BEDA (disepakati): tombol Batal Ready; kursi memakai nickname. |
| 21 | Chicken Hand · Mulai Main | `16:148` Game berlangsung | BEDA (disepakati): kartu "Kamu · nickname" menggantikan "Kamu · P1". |
| 22 | Chicken Hand · Mulai Main | `158:1299` Scan Meja / Izin kamera ditolak | SESUAI |
| 23 | Chicken Hand · Mulai Main | `158:1381` Scan Meja / QR tidak dikenali | SESUAI |
| 24 | Chicken Hand · Mulai Main | `158:1465` Meja Terdeteksi / Memuat | SESUAI |
| 25 | Chicken Hand · Mulai Main | `158:1533` Meja Terdeteksi / Offline | SESUAI |
| 26 | Chicken Hand · Mulai Main | `158:1597` Lobby / Memuat | SESUAI |
| 27 | Chicken Hand · Mulai Main | `158:1644` Lobby / Koneksi terputus | BEDA (disepakati): tombol Ready seperti 16:53. |
| 28 | Chicken Hand · Mulai Main | `158:1714` Game berlangsung / Koneksi terputus | BEDA (disepakati): kartu "Kamu · nickname" seperti 16:148. |
| 29 | Menang | `95:365` Menang | SESUAI |
| 30 | Menang | `110:960` Win From | BEDA (disepakati): pilihan dan kicker memakai nickname, bukan P2–P4. |
| 31 | Menang | `16:244` Poin Menang · dari P2 | BEDA (disepakati): baris dan kicker memakai nickname. |
| 32 | Menang | `96:709` Poin Menang · dari P3 | BEDA (disepakati): template sama dengan 16:244 (nickname); tidak dipotret terpisah. |
| 33 | Menang | `96:761` Poin Menang · dari P4 | BEDA (disepakati): template sama dengan 16:244 (nickname); tidak dipotret terpisah. |
| 34 | Menang | `96:813` Poin Menang · dari Deck | BEDA (disepakati): baris memakai nickname. |
| 35 | Menang | `160:1486` Poin Menang / Menyimpan | SESUAI |
| 36 | Menang | `160:1587` Poin Menang / Gagal menyimpan | SESUAI |
| 37 | Kalah & Selesai | `16:227` Kalah · dari Deck | BEDA (disepakati): chip dan teks memakai nickname. |
| 38 | Kalah & Selesai | `96:892` Kalah · dari Pemain | BEDA (disepakati): chip dan teks memakai nickname. |
| 39 | Kalah & Selesai | `95:377` Sesi Selesai | SESUAI |
| 40 | Kalah & Selesai | `23:73` Leaderboard | SESUAI |
| 41 | Kalah & Selesai | `160:1645` Kalah / Gagal menyimpan | BEDA (disepakati): chip memakai nickname. |
| 42 | Kalah & Selesai | `160:1691` Leaderboard / Memuat | SESUAI |
| 43 | Kalah & Selesai | `160:1823` Leaderboard / Kosong | SESUAI. Versi tamu menampilkan "Masuk untuk ikut bermain"; versi login dengan "Scan Meja" diuji di `test_logged_in_empty_leaderboard_offers_a_table_scan`. |
| 44 | Kalah & Selesai | `160:1903` Leaderboard / Gagal memuat | SESUAI |
| 45 | Kalah & Selesai | `193:1788` Leaderboard / Bulan Ini | SESUAI |
| 46 | Kalah & Selesai | `193:1823` Leaderboard / Minggu Ini | SESUAI |
| 47 | Profil | `152:1106` Profil | SESUAI |
| 48 | Profil | `152:1203` Ubah Foto Profil | SESUAI |
| 49 | Profil | `152:1285` Ubah Username | BEDA (disepakati): petunjuk "Maksimal 15 karakter, tanpa tanda @." sesuai aturan nickname. |
| 50 | Profil | `152:1334` Ubah Sandi | BEDA (disepakati): baris keempat "Minimal satu huruf besar". |
| 51 | Profil | `160:1984` Profil / Memuat | SESUAI |
| 52 | Profil | `160:2088` Profil / Offline | SESUAI |
| 53 | Profil | `160:2168` Ubah Foto Profil / Gagal mengunggah | SESUAI |
| 54 | Profil | `160:2217` Ubah Username / Sudah dipakai | BEDA (disepakati): petunjuk aturan nickname seperti 152:1285. |
| 55 | Profil | `160:2237` Ubah Sandi / Tidak cocok | BEDA (disepakati): baris keempat aturan sandi. |
| 56 | Profil | `172:1750` Ubah Foto Profil / Berhasil | SESUAI |
| 57 | Profil | `172:1786` Ubah Username / Berhasil | SESUAI |
| 58 | Profil | `172:1822` Ubah Sandi / Berhasil | SESUAI |
| 59 | Profil | `173:1758` Ubah Foto Profil / Menyimpan | SESUAI |
| 60 | Profil | `173:1807` Ubah Username / Menyimpan | SESUAI |
| 61 | Profil | `173:1829` Ubah Sandi / Menyimpan | BEDA (disepakati): baris keempat aturan sandi. |
| 62 | Profil | `198:2104` Ubah Foto Profil / Foto dihapus | SESUAI |
| 63 | Konfirmasi & Bantuan | `198:2065` Dialog · Akhiri sesi | SESUAI |
| 64 | Konfirmasi & Bantuan | `198:2078` Dialog · Keluar akun | SESUAI |
| 65 | Konfirmasi & Bantuan | `198:2091` Dialog · Hapus foto | SESUAI |
| 66 | Konfirmasi & Bantuan | `198:2229` Bantuan | BEDA (disepakati): pertanyaan keempat "Lupa sandi?". |

Commit: "Warn against enabling the token page on the server…".

## Tahap 0 — Token dan komponen

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 0.1 | 18 warna primitif, 23 alpha, 20 gaya teks, 16 spacing, 10 radius, 5 stroke, 7 nilai grid, dan 14 shadow (offset, blur, spread, warna) sama dengan Figma Foundations (67:58, 71:202, 72:130). | — | `resources/css/poin.css` blok `:root`; laporan verifikasi Tahap 0 sebelumnya. | Sesuai |
| 0.2 | Semua komponen page Components (77:95) punya padanan Blade, termasuk List/OptionRow (79:138) di tiga state. | — | `resources/views/components/poin/*`. | Sesuai |
| 0.3 | Font Baloo 2 dan Nunito di-host sendiri (4 woff2, 135.288 byte, lisensi OFL), preload untuk set latin, `font-display: swap`, tanpa Google Fonts di layout poin. | — | `public/fonts/poin/`, `test_points_fonts_are_self_hosted_with_their_licences` (baru). | Sesuai |
| 0.4 | `/dev/tokens` hanya aktif bila `isLocal()` dan `POINTS_DEV_PAGES=true`. | — | `TokensController.php:11`, tiga test kondisi di `PoinDesignTokensTest`. | Sesuai |
| 0.5 | Ukuran ikon 20 px dan lebar shine 14/20 sesuai Figma. | — | `poin.css` `.pn-icon--20`, `--size-shine-end`; commit `3e492ea`. | Sesuai |
| 0.6 | Frame "States" (156:347) di Figma kosong; status-banner, spinner dan skeleton hanya bisa dicocokkan per layar. | RENDAH | `get_metadata 156:347` tanpa anak. | Pertanyaan |
| 0.7 | Halaman booking dan dashboard masih memuat Google Fonts (di luar sistem poin). | RENDAH | `resources/views/layouts/app.blade.php:9-10`, `dashboard.blade.php:7-8`, `auth/login.blade.php`, `cover/index.blade.php`. | Pertanyaan |

Commit: "Guard the self-hosted points fonts with a test…".

## Keputusan setelah audit (5 November 2026)

| K | Keputusan | Hasil | Bukti | Commit |
|---|---|---|---|---|
| K1 | Game ditinggal ditutup otomatis: `playing` setelah 3 jam tanpa aktivitas, lobby belum Start setelah 45 menit. | Command `poin:close-idle-games` dijadwalkan setiap 5 menit. Aktivitas = nilai terbaru dari `games.updated_at` (Ready, Start, catat ronde, undo) dan `game_players.last_seen_at` (diperbarui oleh polling). Game diubah menjadi `finished`; ronde dan poinnya tidak disentuh. Batas waktu ada di `config/poin.php` (`POINTS_IDLE_PLAYING_MINUTES`, `POINTS_IDLE_LOBBY_MINUTES`). | `PoinIdleGamesTest` (5 test, gagal sebelum perbaikan karena command belum ada). | `45ea69e` |
| K2 | Foto di-encode ulang di server dengan GD. | Sisi terpanjang maksimal 512 px; JPG tetap JPG (kualitas 85), PNG tetap PNG dengan transparansi. Hasil encode ulang tidak membawa EXIF. Orientasi EXIF dipakai untuk memutar foto sebelum dibuang, kalau ekstensi `exif` ada. Gambar di atas 4096 px ditolak sebelum diproses. PNG/JPG rusak yang lolos cek header sekarang ditolak (422; sebelumnya disimpan). Throttle `poin-photo` 10 unggahan per menit per pemain. `php-gd` ditambahkan ke `deploy/deploy.sh`. | 5 test baru di `PoinProfileTest` (EXIF/GPS hilang, dimensi 512 px, batas 4096 px, file rusak dan bukan gambar ditolak, throttle per pemain), semuanya gagal sebelum perbaikan. | `2b4b8cd` |
| K3 | File dihapus dari storage saat pemain menekan Hapus foto. | **Tidak dikerjakan.** Layar Figma `198:2104` "Ubah Foto Profil / Foto dihapus" punya tombol Urungkan, dan fitur Urungkan sudah ada sejak Tahap 5 (`POST /profil/foto/urungkan`, `AvatarService::restore`). Menghapus file seketika membuat Urungkan tidak bisa bekerja. Menunggu keputusan. | `resources/views/poin/profile-photo.blade.php:16`. | — |
| K4 | Route situs utama dibatasi ke domain utama. | Middleware `EnsureMainDomain` membungkus semua route di `routes/main.php` (booking, cover, jadwal, login staf, dashboard, `/home`, webhook DOKU dan AYO). Host yang diizinkan: `MAIN_DOMAIN` dan `www.MAIN_DOMAIN`; host lain mendapat 404 sebelum sesi atau auth berjalan. Kalau `MAIN_DOMAIN` kosong (bawaan, termasuk lokal), tidak dibatasi. | `MainDomainTest` (5 test), termasuk webhook tetap menjawab di domain utama dan 404 di subdomain poin, serta pengecekan bahwa setiap route non-poin terlindungi. | `ff55115` |
| K5 | Nickname: spasi dirapatkan dan di-trim; huruf Kiril dibiarkan. | `Player::normalizeNickname` merapatkan spasi, tab, dan spasi Unicode (termasuk NBSP) menjadi satu spasi. Dipakai saat daftar, ubah username, login, dan pembentukan `nickname_key`. Batas 15 karakter dihitung setelah dirapatkan. | 3 test baru di `PlayerAuthTest`, 1 test diperluas di `PoinProfileTest`. | `45edaa6` |
| K6 | Urutan seri: lebih banyak menang, lalu nickname; peringkat berurutan. | Aturan ini sudah ada di `LeaderboardService::ranking` (`nickname_key` unik, jadi urutannya pasti). Tidak ada perubahan kode; urutan dikunci dengan test, termasuk tampilan podium dan landing. | `test_tied_points_rank_by_more_wins_then_nickname_with_sequential_ranks`. | `335445e` |
| K7 | `game_rounds` restrict, escape pencarian, cek DNS tidak menggagalkan deploy. | Migrasi `2026_11_05_000001` mengganti FK `winner_player_id`, `discarder_player_id`, `recorded_by_player_id` menjadi `restrictOnDelete`. Pencarian dashboard memakai `LIKE ? ESCAPE '!'`, jadi `%`, `_` dan `!` dibaca apa adanya. Cek DNS sekarang menampilkan peringatan terpisah bila `hostname -I` gagal, dan deploy tetap lanjut. Catatan: kode lama juga tidak menggagalkan deploy (cek ada di dalam `if`), tapi menyalahkan DNS ketika yang gagal `hostname -I`. | `PoinDataIntegrityTest` (3 test; hapus pemain dan pencarian gagal sebelum perbaikan); simulasi bash empat skenario, semua exit 0. | `0ab1140` |
| K8 | Tabel layar yang berbeda dari Figma. | Laporan sebelumnya menyebut 27; jumlah yang benar **25**, sesuai tabel 66 layar di atas. Lihat tabel di bawah. Tampilan tidak diubah. | Tabel Tahap 7. | — |

### 25 layar yang berbeda dari Figma

| No | Layar | Node | Perbedaan | Alasan | Diputuskan oleh |
|---|---|---|---|---|---|
| 3 | Masuk | `95:411` | Label "EMAIL / NAMA PEMAIN *"; tautan "Lupa sandi?" | Login menerima email atau nickname; pemain yang lupa sandi butuh jalan ke Bantuan | Label: pemilik ("email dan nama pemain"). Tautan: diusulkan Claude (pertanyaan 3 Tahap 6), disetujui pemilik ("nomor 3 boleh") |
| 10 | Masuk / Mengirim | `157:1391` | Sama dengan 95:411 | Sama | Sama dengan No 3 |
| 11 | Masuk / Gagal terhubung | `157:1422` | Sama dengan 95:411 | Sama | Sama dengan No 3 |
| 4 | Pilih Game | `16:21` | Ikon profil di kanan atas | Profil bisa dibuka sebelum masuk meja | Pemilik ("nanti tambahkan ikon profil") |
| 12 | Pilih Game / Memuat | `157:1457` | Ikon profil | Sama | Pemilik |
| 13 | Pilih Game / Gagal memuat | `157:1512` | Ikon profil | Sama | Pemilik |
| 19 | Lobby (menunggu) | `16:53` | Tombol Ready/Batal Ready; kursi memakai nickname | Figma tidak punya tombol Ready; label P1–P4 diganti nama | Pemilik ("beri tombol ready"; "p1-p4 itu nanti nama nickname pemain") |
| 20 | Lobby (siap) | `16:99` | Tombol Batal Ready; nickname | Sama | Pemilik |
| 27 | Lobby / Koneksi terputus | `158:1644` | Tombol Ready | Sama | Pemilik |
| 21 | Game berlangsung | `16:148` | "Kamu · nickname" menggantikan "Kamu · P1" | P1–P4 diganti nama | Pemilik |
| 28 | Game berlangsung / Koneksi terputus | `158:1714` | Sama dengan 16:148 | Sama | Pemilik |
| 30 | Win From | `110:960` | Pilihan dan kicker memakai nickname | Sama | Pemilik |
| 31 | Poin Menang · dari P2 | `16:244` | Baris dan kicker memakai nickname | Sama | Pemilik |
| 32 | Poin Menang · dari P3 | `96:709` | Sama dengan 16:244 | Sama | Pemilik |
| 33 | Poin Menang · dari P4 | `96:761` | Sama dengan 16:244 | Sama | Pemilik |
| 34 | Poin Menang · dari Deck | `96:813` | Baris memakai nickname | Sama | Pemilik |
| 37 | Kalah · dari Deck | `16:227` | Chip dan teks memakai nickname | Sama | Pemilik |
| 38 | Kalah · dari Pemain | `96:892` | Chip dan teks memakai nickname | Sama | Pemilik |
| 41 | Kalah / Gagal menyimpan | `160:1645` | Chip memakai nickname | Sama | Pemilik |
| 49 | Ubah Username | `152:1285` | Petunjuk "Maksimal 15 karakter, tanpa tanda @." (Figma: "3–16 karakter: huruf, angka, dan garis bawah") | Mengikuti aturan nickname saat daftar | Teks dari Claude; pemilik memilih tetap ("tetap saja") |
| 54 | Ubah Username / Sudah dipakai | `160:2217` | Petunjuk seperti 152:1285 | Sama | Sama dengan No 49 |
| 50 | Ubah Sandi | `152:1334` | Baris keempat "Minimal satu huruf besar" | Aturan sandi mewajibkan huruf besar | Pilihan disiapkan Claude; pemilik memilih ("saya pilih tambah baris keempat") |
| 55 | Ubah Sandi / Tidak cocok | `160:2237` | Baris keempat | Sama | Pemilik |
| 61 | Ubah Sandi / Menyimpan | `173:1829` | Baris keempat | Sama | Pemilik |
| 66 | Bantuan | `198:2229` | Pertanyaan keempat "Lupa sandi?" | Pemilik meminta lupa sandi dijelaskan di Bantuan ("dijelaskan saja di halaman bantuan"); teks jawaban ditulis Claude dan tidak dikoreksi | Arah: pemilik. Teks: Claude |

Tautan "Lupa sandi?" di layar Masuk mengarah ke `route('poin.help')`, yaitu `https://poin.hzmahjong.tech/bantuan`, dan membuka bagian paling atas halaman. Tautan itu tidak punya anchor, dan jawaban "Lupa sandi?" di Pertanyaan Umum masih tertutup sampai diketuk. Isi jawabannya: "Temui staf atau admin venue untuk mengatur ulang sandimu. Setelah masuk dengan sandi sementara, ganti sandi lewat Profil."

### Catatan deploy

- Server butuh `MAIN_DOMAIN=hzmahjong.tech` di `/root/hzmahjong.env`. Tanpa baris ini, pembatasan domain tidak aktif.
- Dua migrasi baru (`undo_floor` dan FK restrict `game_rounds`) jalan otomatis lewat `migrate --force`. Migrasi FK belum pernah dicoba di MySQL; di test hanya SQLite.
- Nickname lama yang sudah tersimpan dengan spasi ganda tidak diubah; perapian berlaku saat disimpan berikutnya.

## Permintaan client 9 November 2026

| No | Permintaan | Hasil | Keputusan client |
|---|---|---|---|
| C1 | Pemain kalah konfirmasi kemenangan sebelum ronde berikutnya | Klaim menang menunggu konfirmasi ketiga pemain kalah. Poin baru tercatat setelah semua setuju. Satu penolakan membatalkan klaim dan pemenang mengisi ulang. Pemain yang sudah membuka layar konfirmasi dianggap setuju setelah 15 detik; pemain yang belum membukanya (HP mati, layar lain) dianggap setuju 30 detik setelah klaim dibuat. Pemenang bisa membatalkan klaimnya. Berhenti sesi membuang klaim yang belum selesai. | Ketiga pemain kalah; ditolak = isi ulang; 15 detik tanpa respons = setuju (awalnya 5, diubah menjadi 15) |
| C2 | Pilih tempat duduk, kursi diberi nama angin | Kursi 1–4 = East, South, West, North (hanya penanda, tidak berputar). Di lobby, kursi kosong bisa diketuk untuk pindah; status Ready batal saat pindah. Peta posisi di layar game dan layar meja memakai huruf E/S/W/N. | Nama angin dalam bahasa Inggris; penanda tempat duduk |

Layar baru yang tidak ada di Figma: Konfirmasi Kemenangan (pemain kalah: Setuju/Tolak dengan hitung mundur) dan Menunggu Konfirmasi (pemenang: status tiap pemain, Batalkan Klaim). Keduanya memakai komponen dan token yang sudah ada. Kursi lobby menampilkan nama angin di atas avatar; kursi kosong bertanda PILIH.

Batas waktu bisa diubah lewat `POINTS_CONFIRM_SECONDS` dan `POINTS_CONFIRM_UNSEEN_SECONDS`.
