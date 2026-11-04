<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Services\Poin\LobbyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PoinHelpDialogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function as(Player $player): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession([Player::SESSION_FINGERPRINT => $player->passwordFingerprint()]);

        return $this->actingAs($player, 'player');
    }

    private function player(string $name = 'Rina'): Player
    {
        return Player::create(['nickname' => $name, 'email' => strtolower($name) . '@example.com', 'password' => 'Rahasia123']);
    }

    public function test_help_page_explains_points_and_forgotten_passwords_to_guests(): void
    {
        config(['app.points_staff_whatsapp' => '']);

        $this->get(route('poin.help'))->assertOk()
            ->assertSee('href="' . route('poin.login') . '"', false)
            ->assertSeeInOrder([
                'CARA HITUNG POIN',
                'Menang dari tile pemain lain',
                'Pemenang +4 · yang membuang tile −2 · dua pemain lain masing-masing −1',
                'Menang dari deck',
                'Pemenang +6 · tiga pemain lain masing-masing −2',
                'PERTANYAAN UMUM',
                'Main di venue',
                'Urutan giliran',
                'Poinku salah?',
                'Lupa sandi?',
                'Temui staf atau admin venue untuk mengatur ulang sandimu.',
            ])
            ->assertDontSee('Hubungi Staf Venue');
    }

    public function test_login_links_forgotten_passwords_to_the_help_page(): void
    {
        $this->get(route('poin.login'))->assertOk()
            ->assertSee('Lupa sandi?')
            ->assertSee('href="' . route('poin.help') . '"', false);
    }

    public function test_help_page_returns_players_to_their_profile_and_links_the_staff(): void
    {
        config(['app.points_staff_whatsapp' => '6281234567890']);
        $this->as($this->player());

        $this->get(route('poin.help'))->assertOk()
            ->assertSee('href="' . route('poin.profile') . '"', false)
            ->assertSee('Hubungi Staf Venue')
            ->assertSee('https://wa.me/6281234567890', false);

        $this->get(route('poin.profile.content'))->assertSee(route('poin.help'), false);
    }

    public function test_logout_and_photo_removal_ask_for_confirmation(): void
    {
        $this->as($this->player());

        $this->get(route('poin.profile'))->assertOk()
            ->assertSee('Keluar dari akun?')
            ->assertSee('Kamu perlu masuk lagi untuk bermain dan mengumpulkan poin.')
            ->assertSee('Ya, keluar')
            ->assertSee('action="' . route('poin.logout') . '"', false);
        $this->get(route('poin.profile.content'))->assertSee('data-poin-dialog-open="pn-logout-dialog"', false);

        $this->get(route('poin.profile.photo'))->assertOk()
            ->assertSee('Hapus foto profil?')
            ->assertSee('Foto akan diganti dengan siluet bawaan. Kamu bisa mengunggah foto baru kapan saja.')
            ->assertSee('Ya, hapus foto')
            ->assertSee('data-poin-dialog-open="pn-photo-remove-dialog"', false)
            ->assertSee('action="' . route('poin.profile.photo.remove') . '"', false);
    }

    public function test_stopping_a_session_asks_for_confirmation_once_per_page(): void
    {
        $table = MahjongTable::create(['name' => 'Meja 1 - Reguler', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1]);
        $players = collect(['Satu', 'Dua', 'Tiga', 'Empat'])->map(fn ($name) => $this->player($name));

        foreach ($players as $player) {
            $this->as($player)->post(route('poin.table.join', $table->qr_code));
            $this->post(route('poin.lobby.ready', Game::sole()), ['ready' => 1]);
        }

        $game = Game::sole();
        app(LobbyService::class)->start($game);
        $this->as($players[1])->get(route('poin.round.win', $game))->assertSee('Akhiri sesi bermain?');
        $this->as($players[0])->postJson(route('poin.round.store', $game), ['ronde' => 1, 'dari' => 'deck']);

        $html = $this->get(route('poin.round.result', $game))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="pn-stop-dialog"'));
        $this->assertStringContainsString('Akhiri sesi bermain?', $html);
        $this->assertSame(2, substr_count($html, 'data-poin-dialog-open="pn-stop-dialog"'));
        $this->assertStringContainsString('Poinmu tetap tersimpan di leaderboard. Kamu bisa mulai lagi kapan saja di meja venue.', $html);
        $this->assertStringContainsString('Ya, akhiri sesi', $html);
        $this->assertStringContainsString('action="' . route('poin.round.stop', $game) . '"', $html);

        $this->post(route('poin.round.stop', $game));
        $this->post(route('poin.round.stop', $game));
        $this->assertSame(Game::STATUS_FINISHED, $game->fresh()->status);
        $this->assertSame(1, $game->rounds()->count());
    }

    public function test_password_rules_list_the_capital_letter_and_games_link_the_profile(): void
    {
        $this->as($this->player());

        $this->get(route('poin.profile.password'))->assertSee('Minimal satu huruf besar');
        $this->get(route('poin.games'))->assertSee('href="' . route('poin.profile') . '"', false);
    }
}
