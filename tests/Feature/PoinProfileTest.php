<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\PlayerRoundScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PoinProfileTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private Player $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake(Player::AVATAR_DISK);
        $this->player = Player::create(['nickname' => 'Rina', 'email' => 'rina@example.com', 'password' => 'Rahasia123']);
        $this->as($this->player);
    }

    private function as(Player $player): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession([Player::SESSION_FINGERPRINT => $player->passwordFingerprint()]);

        return $this->actingAs($player, 'player');
    }

    private function png(string $name = 'wajah.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    public function test_guests_are_sent_to_login(): void
    {
        Auth::forgetGuards();
        $this->flushSession();

        $this->get(route('poin.profile'))->assertRedirect(route('poin.login'));
        $this->get(route('poin.profile.photo'))->assertRedirect(route('poin.login'));
    }

    public function test_profile_shows_the_figma_texts_and_the_players_totals(): void
    {
        PlayerRoundScore::create(['player_id' => $this->player->id, 'points' => 6, 'is_winner' => true, 'played_at' => now()]);
        PlayerRoundScore::create(['player_id' => $this->player->id, 'points' => -2, 'is_winner' => false, 'played_at' => now()]);

        $this->get(route('poin.profile'))->assertOk()
            ->assertSee('Profil')
            ->assertSee('Data profilmu belum bisa dimuat. Sambungkan internet lalu coba lagi.')
            ->assertSee(route('poin.profile.content'), false);

        $this->get(route('poin.profile.content'))->assertOk()
            ->assertSeeInOrder(['Kamu', '@Rina', '4', 'POIN', '2', 'MAIN', '1', 'MENANG', 'AKUN', 'Ubah Foto Profil', 'Ubah Username', 'Ubah Sandi', 'Bantuan', 'Keluar']);
    }

    public function test_app_bars_link_to_the_profile(): void
    {
        $this->get(route('poin.scan'))->assertSee('href="' . route('poin.profile') . '"', false);
    }

    public function test_username_can_be_changed(): void
    {
        $this->get(route('poin.profile.username'))->assertOk()
            ->assertSee('Username tampil di leaderboard dan dilihat semua pemain.')
            ->assertSee('USERNAME BARU')
            ->assertSee('placeholder="Rina"', false);

        $this->postJson(route('poin.profile.username'), ['nickname' => ' Rina W. '])
            ->assertOk()->assertJson(['redirect' => route('poin.profile')]);

        $this->assertSame('Rina W.', $this->player->fresh()->nickname);
        $this->get(route('poin.profile'))->assertSee('Username berhasil diubah.');
        $this->get(route('poin.profile'))->assertDontSee('Username berhasil diubah.');
    }

    public function test_username_must_be_free_and_valid(): void
    {
        Player::create(['nickname' => 'Budi', 'email' => 'budi@example.com', 'password' => 'Rahasia123']);

        $this->postJson(route('poin.profile.username'), ['nickname' => 'BUDI'])
            ->assertStatus(422)->assertJsonFragment(['Username sudah dipakai. Coba yang lain.']);
        $this->postJson(route('poin.profile.username'), ['nickname' => 'ri@na'])->assertStatus(422);
        $this->postJson(route('poin.profile.username'), ['nickname' => str_repeat('a', 16)])->assertStatus(422);
        $this->postJson(route('poin.profile.username'), ['nickname' => 'RINA'])->assertOk();

        $this->assertSame('RINA', $this->player->fresh()->nickname);
    }

    public function test_password_change_checks_the_old_password_and_the_repeat(): void
    {
        $this->get(route('poin.profile.password'))->assertOk()
            ->assertSee('Masukkan sandi lama, lalu buat sandi baru yang sulit ditebak.')
            ->assertSeeInOrder(['SANDI LAMA', 'SANDI BARU', 'ULANGI SANDI BARU', 'Minimal 8 karakter', 'Mengandung huruf dan angka', 'Sama dengan ulangi sandi']);

        $this->postJson(route('poin.profile.password'), ['sandi_lama' => 'Salah1234', 'sandi_baru' => 'Baru12345', 'ulangi_sandi' => 'Baru12345'])
            ->assertStatus(422)->assertJsonFragment(['Sandi lama salah.']);
        $this->postJson(route('poin.profile.password'), ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'Baru12345', 'ulangi_sandi' => 'Baru00000'])
            ->assertStatus(422)->assertJsonFragment(['Sandi tidak cocok dengan sandi baru.']);
        $this->postJson(route('poin.profile.password'), ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'lemah123', 'ulangi_sandi' => 'lemah123'])
            ->assertStatus(422)->assertJsonFragment([Player::PASSWORD_MESSAGE]);

        $this->assertTrue(Hash::check('Rahasia123', $this->player->fresh()->password));
    }

    public function test_password_change_keeps_this_session_and_ends_the_others(): void
    {
        $oldFingerprint = $this->player->passwordFingerprint();

        $this->postJson(route('poin.profile.password'), ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'Baru12345', 'ulangi_sandi' => 'Baru12345'])
            ->assertOk()->assertJson(['redirect' => route('poin.profile')]);

        $this->assertTrue(Hash::check('Baru12345', $this->player->fresh()->password));
        $this->get(route('poin.profile'))->assertOk()->assertSee('Sandi berhasil diubah.');

        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession([Player::SESSION_FINGERPRINT => $oldFingerprint])->actingAs($this->player->fresh(), 'player');
        $this->get(route('poin.profile'))->assertRedirect(route('poin.login'));
    }

    public function test_photo_page_shows_the_figma_texts(): void
    {
        $this->get(route('poin.profile.photo'))->assertOk()
            ->assertSee('Gunakan foto wajah yang jelas. Maksimal 5 MB, format JPG atau PNG.')
            ->assertSeeInOrder(['PILIH SUMBER', 'Ambil Foto', 'Pilih dari Galeri', 'Hapus Foto', 'Simpan'])
            ->assertSee('capture="user"', false)
            ->assertSee('Coba Unggah Lagi', false);
    }

    public function test_a_photo_is_stored_under_a_random_name_and_replaces_the_old_one(): void
    {
        $this->postJson(route('poin.profile.photo.update'), ['foto' => $this->png('../../shell.php.png')])
            ->assertOk()->assertJson(['redirect' => route('poin.profile')]);

        $first = $this->player->fresh()->avatar_path;
        $this->assertMatchesRegularExpression('#^avatars/[A-Za-z0-9]{40}\.png$#', $first);
        Storage::disk(Player::AVATAR_DISK)->assertExists($first);
        $this->get(route('poin.profile'))->assertSee('Foto profil berhasil diperbarui.');
        $this->get(route('poin.profile.content'))->assertSee('storage/' . $first, false);

        $this->postJson(route('poin.profile.photo.update'), ['foto' => $this->png()])->assertOk();

        Storage::disk(Player::AVATAR_DISK)->assertMissing($first);
        Storage::disk(Player::AVATAR_DISK)->assertExists($this->player->fresh()->avatar_path);
    }

    public function test_only_small_jpg_or_png_files_are_accepted(): void
    {
        $this->postJson(route('poin.profile.photo.update'), ['foto' => UploadedFile::fake()->createWithContent('wajah.jpg', '<?php echo 1;')])
            ->assertStatus(422)->assertJsonFragment(['Format foto harus JPG atau PNG.']);
        $this->postJson(route('poin.profile.photo.update'), ['foto' => UploadedFile::fake()->create('besar.png', Player::AVATAR_MAX_KB + 1, 'image/png')])
            ->assertStatus(422);

        $this->assertNull($this->player->fresh()->avatar_path);
        $this->assertSame([], Storage::disk(Player::AVATAR_DISK)->allFiles());
    }

    public function test_saving_without_a_new_photo_just_returns_to_the_profile(): void
    {
        $this->postJson(route('poin.profile.photo.update'))->assertOk()->assertJson(['redirect' => route('poin.profile')]);

        $this->get(route('poin.profile'))->assertDontSee('Foto profil berhasil diperbarui.');
    }

    public function test_a_removed_photo_can_be_restored(): void
    {
        $this->postJson(route('poin.profile.photo.update'), ['foto' => $this->png()]);
        $path = $this->player->fresh()->avatar_path;

        $this->post(route('poin.profile.photo.remove'))->assertRedirect(route('poin.profile.photo'));
        $this->assertNull($this->player->fresh()->avatar_path);
        Storage::disk(Player::AVATAR_DISK)->assertExists($path);

        $this->get(route('poin.profile.photo'))->assertSee('Foto profil dihapus.')->assertSee('Urungkan');
        $this->get(route('poin.profile.photo'))->assertDontSee('Foto profil dihapus.');

        $this->post(route('poin.profile.photo.restore'))->assertRedirect(route('poin.profile.photo'));
        $this->assertSame($path, $this->player->fresh()->avatar_path);
    }

    public function test_a_new_photo_after_removal_deletes_the_removed_file(): void
    {
        $this->postJson(route('poin.profile.photo.update'), ['foto' => $this->png()]);
        $path = $this->player->fresh()->avatar_path;
        $this->post(route('poin.profile.photo.remove'));

        $this->postJson(route('poin.profile.photo.update'), ['foto' => $this->png()]);

        Storage::disk(Player::AVATAR_DISK)->assertMissing($path);
        $this->assertNull($this->player->fresh()->avatar_previous_path);
        $this->post(route('poin.profile.photo.restore'));
        $this->assertNotSame($path, $this->player->fresh()->avatar_path);
    }

    public function test_logout_from_the_profile(): void
    {
        $this->post(route('poin.logout'))->assertRedirect(route('poin.login'));
        $this->assertGuest('player');
    }
}
