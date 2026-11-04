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
