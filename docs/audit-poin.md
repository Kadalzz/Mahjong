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
| 1.8 | Route situs utama (booking, `/login` staf, dashboard) juga melayani host subdomain poin karena tidak dibatasi domain. Tidak ada kebocoran (sesi per host), tapi `poin.hzmahjong.tech/dashboard` menampilkan login staf. | RENDAH | `routes/web.php` tanpa `Route::domain`. | Pertanyaan |
| 1.9 | Waktu respons login berbeda antara akun ada dan tidak ada (hash hanya dihitung bila akun ada). Keberadaan akun memang sudah terlihat dari pesan "sudah dipakai" saat daftar. | RENDAH | `Auth::guard('player')->attempt` tanpa timebox. | Ditunda |
| 1.10 | Nama yang mirip secara visual (huruf Kiril "а" vs Latin "a", spasi ganda) dianggap berbeda. | RENDAH | `Player::nicknameKey` hanya `mb_strtolower(trim())`. | Pertanyaan |
| 1.11 | Pencarian pemain di dashboard tidak meng-escape `%`/`_` (tetap memakai binding, hanya hasil pencarian lebih luas). | RENDAH | `Dashboard/PlayerController.php:17`. | Ditunda |

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
| 4.8 | Foreign key `players` di `game_rounds` memakai `cascadeOnDelete`: kalau satu pemain dihapus langsung dari database, ronde yang ia menangkan beserta poin tiga pemain lain ikut terhapus. Belum ada fitur hapus pemain. | RENDAH | `database/migrations/2026_11_03_000003_create_game_rounds_table.php:15-17`. | Ditunda |
| 4.9 | Layar Menang, Kalah, Sesi Selesai, Leaderboard dan kondisinya. | — | Lihat daftar 66 layar di Tahap 7. | Lihat Tahap 7 |

Commit: "Stop a second undo from deleting the round before…".

## Tahap 5 — Profil

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 5.1 | Foto disimpan apa adanya tanpa encode ulang di server. Metadata EXIF (termasuk lokasi GPS dari kamera HP) ikut tersimpan dan bisa diunduh siapa pun yang melihat avatar. Normalnya aman karena browser meng-encode ulang lewat canvas (`resources/js/poin.js:113-131`), tapi tidak kalau `createImageBitmap` gagal atau request dikirim langsung. Perbaikan butuh paket `php-gd` (belum terpasang di server, `deploy/deploy.sh:25-27`). | SEDANG | `AvatarService::store` memakai `storeAs` pada file asli. | Pertanyaan |
| 5.2 | Tidak ada batas dimensi gambar, hanya ukuran file 5 MB. PNG 5 MB bisa berukuran puluhan ribu piksel dan membuat tab browser pemain lain crash saat avatar tampil di lobby atau leaderboard. | SEDANG | `ProfileController::updatePhoto` aturan `dimensions:min_width=1,min_height=1`. | Pertanyaan |
| 5.3 | Foto yang dihapus tetap tersimpan (untuk Urungkan) dan masih bisa dibuka lewat URL acak 40 karakter sampai pemain mengunggah foto baru. | RENDAH | `AvatarService::remove`. | Pertanyaan |
| 5.4 | Isi file diperiksa (mimes dari isi, mimetypes, `dimensions` membaca header gambar), nama file `.php` ditolak, nama tersimpan acak dengan ekstensi dari isi, gambar berisi kode tetap disimpan sebagai `.png` statis. | — | `test_only_small_jpg_or_png_files_are_accepted`, `test_a_photo_is_stored_under_a_random_name_and_replaces_the_old_one`, `test_an_image_carrying_code_is_kept_as_a_plain_image_file` (baru). nginx hanya meneruskan `\.php$` ke PHP dan mengirim `X-Content-Type-Options: nosniff`. | Sesuai |
| 5.5 | Foto lama dihapus saat diganti; foto yang dihapus lalu diganti foto baru ikut dihapus. | — | `test_a_new_photo_after_removal_deletes_the_removed_file`. | Sesuai |
| 5.6 | Ubah username mengikuti aturan nickname (termasuk karakter tersembunyi dari 1.1). | — | `test_username_must_be_free_and_valid`. | Sesuai |
| 5.7 | Ubah sandi meminta sandi lama, sesi ini tetap, sesi lain berakhir; throttle 10/menit. | — | `test_password_change_checks_the_old_password_and_the_repeat`, `test_password_change_keeps_this_session_and_ends_the_others`, `routes/poin.php:44`. | Sesuai |
| 5.8 | Semua aksi profil memakai pemain yang sedang login, tanpa ID dari request, jadi tidak ada jalur mengubah akun lain. | — | `ProfileController::player()`; tidak ada parameter route. | Sesuai |
| 5.9 | Unggah foto tidak di-throttle. Penyimpanan per pemain tetap terbatas (file lama dihapus). | RENDAH | `routes/poin.php:37`. | Ditunda |

Commit: "Cover image uploads that carry code…".

## Tahap 3 — Mulai main

| # | Temuan | Tingkat | Bukti | Status |
|---|---|---|---|---|
| 3.1 | Dua request gabung yang datang bersamaan dari pemain yang sama ke dua meja berbeda bisa sama-sama lolos, karena transaksi hanya mengunci baris meja. Pemain lalu duduk di dua game aktif. | SEDANG | `LobbyService::join` sebelumnya hanya `MahjongTable::lockForUpdate`. Tidak bisa direproduksi lewat test: SQLite in-memory menjalankan satu koneksi, jadi dua transaksi tidak pernah berjalan bersamaan. Perbaikan: kunci baris pemain lebih dulu, lalu meja (urutan tetap, tanpa deadlock). | Diperbaiki |
| 3.2 | Game berstatus "playing" yang ditinggal semua pemain tidak pernah berakhir. Meja itu lalu menolak pemain baru ("sedang dipakai") sampai ada yang menekan Berhenti. | SEDANG | `LobbyService::tableStatus` → `playing`; tidak ada job atau scheduler yang menutup game lama (`routes/console.php`). Butuh keputusan batas waktu. | Pertanyaan |
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
| 2.4 | Urutan poin, lalu jumlah menang, lalu nama; nomor peringkat berurutan (nilai seri tidak berbagi peringkat). | RENDAH | `LeaderboardService::ranking`, `test_public_leaderboard_needs_no_login_and_orders_by_points_then_wins`. Seri berbagi peringkat atau tidak adalah keputusan produk. | Pertanyaan |
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
