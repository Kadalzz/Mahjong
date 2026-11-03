<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Models\User;
use App\Services\Poin\LobbyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PoinGameFlowTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->table = MahjongTable::create(['name' => 'Meja 1 - Reguler', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1]);
    }

    private function player(string $nickname): Player
    {
        return Player::create(['nickname' => $nickname, 'email' => strtolower($nickname) . '@example.com', 'password' => 'Rahasia123']);
    }

    private function as(Player $player): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession([Player::SESSION_FINGERPRINT => $player->passwordFingerprint()]);

        return $this->actingAs($player, 'player');
    }

    private function fullLobby(bool $ready = true): Game
    {
        foreach (['Satu', 'Dua', 'Tiga', 'Empat'] as $name) {
            $this->as($this->player($name))->post(route('poin.table.join', $this->table->qr_code));

            if ($ready) {
                $this->post(route('poin.lobby.ready', Game::sole()), ['ready' => 1]);
            }
        }

        return Game::sole();
    }

    public function test_every_table_gets_a_unique_qr_code(): void
    {
        $other = MahjongTable::create(['name' => 'Meja 2', 'capacity' => 4, 'status' => 'available']);

        $this->assertNotEmpty($this->table->qr_code);
        $this->assertNotSame($this->table->qr_code, $other->qr_code);
    }

    public function test_table_label_and_number_are_shortened_for_the_screens(): void
    {
        $this->assertSame('Meja 1', $this->table->displayName());
        $this->assertSame('1', $this->table->number());
    }

    public function test_scan_page_needs_login_and_shows_the_figma_texts(): void
    {
        $this->get(route('poin.scan'))->assertRedirect(route('poin.login'));

        $this->as($this->player('Rina'))->get(route('poin.scan'))->assertOk()
            ->assertSee('Arahkan kamera ke QR di meja')
            ->assertSee('Izin kamera ditolak')
            ->assertSee('QR tidak dikenali')
            ->assertSee('Masukkan Kode Meja');
    }

    public function test_guest_scanning_a_table_qr_returns_to_that_table_after_login(): void
    {
        $player = $this->player('Rina');

        $this->get(route('poin.table', $this->table->qr_code))->assertRedirect(route('poin.login'));

        $this->post(route('poin.login'), ['login' => $player->email, 'password' => 'Rahasia123'])
            ->assertRedirect(route('poin.table', $this->table->qr_code));
    }

    public function test_unknown_qr_goes_back_to_scan_with_the_unknown_state(): void
    {
        $this->as($this->player('Rina'));

        $this->get(route('poin.table', 'NGAWUR'))->assertRedirect(route('poin.scan', ['status' => 'unknown']));
        $this->get(route('poin.table.content', 'NGAWUR'))->assertNotFound();
    }

    public function test_detected_table_screen_shows_status_and_is_case_insensitive(): void
    {
        $this->as($this->player('Rina'));

        $this->get(route('poin.table.content', strtolower($this->table->qr_code)))->assertOk()
            ->assertSee('QR TERBACA')
            ->assertSee('Meja otomatis terpilih sesuai QR yang kamu scan.')
            ->assertSee('Tersedia')
            ->assertSee('0 dari 4')
            ->assertSee('Masuk Lobby Meja 1');
    }

    public function test_players_get_seats_in_order_and_are_not_ready_when_they_join(): void
    {
        $game = $this->fullLobby(ready: false);

        $this->assertSame([1, 2, 3, 4], $game->seats->pluck('seat')->all());
        $this->assertTrue($game->seats->every(fn ($seat) => !$seat->is_ready));
        $this->assertSame(Game::STATUS_LOBBY, $game->status);
    }

    public function test_players_toggle_ready_and_a_full_table_waits_for_everyone(): void
    {
        $game = $this->fullLobby(ready: false);
        $last = $game->seats->last()->player;

        $this->as($last)->post(route('poin.lobby.ready', $game), ['ready' => 1])->assertRedirect(route('poin.lobby', $game));
        $this->assertTrue($game->seats()->where('player_id', $last->id)->sole()->is_ready);

        $html = $this->get(route('poin.lobby.status', $game))->json('html');
        $this->assertStringContainsString('Menunggu semua pemain Ready (1/4)', $html);
        $this->assertStringContainsString('Batal Ready', $html);

        $this->post(route('poin.lobby.start', $game))->assertRedirect(route('poin.lobby', $game));
        $this->assertSame(Game::STATUS_LOBBY, $game->fresh()->status);

        $this->post(route('poin.lobby.ready', $game), ['ready' => 0]);
        $this->assertFalse($game->seats()->where('player_id', $last->id)->sole()->is_ready);
    }

    public function test_lobby_seats_show_the_other_players_nicknames(): void
    {
        $game = $this->fullLobby(ready: false);

        $html = $this->as($game->seats->first()->player)->get(route('poin.lobby.status', $game))->json('html');

        foreach (['Kamu', 'Dua', 'Tiga', 'Empat'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringNotContainsString('>Satu<', $html);
    }

    public function test_ready_cannot_change_after_the_game_starts(): void
    {
        $game = $this->fullLobby();
        app(LobbyService::class)->start($game);
        $player = $game->seats->first()->player;

        $this->as($player)->post(route('poin.lobby.ready', $game), ['ready' => 0])->assertRedirect(route('poin.game', $game));
        $this->assertTrue($game->seats()->where('player_id', $player->id)->sole()->is_ready);
    }

    public function test_fifth_player_is_refused_when_the_table_is_full(): void
    {
        $game = $this->fullLobby();

        $this->as($this->player('Lima'))->post(route('poin.table.join', $this->table->qr_code))
            ->assertRedirect(route('poin.table', $this->table->qr_code));

        $this->assertSame(4, $game->seats()->count());
        $this->get(route('poin.table.content', $this->table->qr_code))->assertSee('Penuh');
    }

    public function test_joining_twice_keeps_the_same_seat(): void
    {
        $player = $this->player('Rina');

        $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
        $this->post(route('poin.table.join', $this->table->qr_code));

        $this->assertSame(1, GamePlayer::where('player_id', $player->id)->count());
    }

    public function test_joining_another_table_moves_the_player_out_of_the_old_lobby(): void
    {
        $other = MahjongTable::create(['name' => 'Meja 2', 'capacity' => 4, 'status' => 'available']);
        $player = $this->player('Rina');

        $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
        $this->post(route('poin.table.join', $other->qr_code));

        $this->assertSame($other->id, app(LobbyService::class)->activeSeatFor($player)->game->mahjong_table_id);
        $this->assertSame(Game::STATUS_FINISHED, Game::where('mahjong_table_id', $this->table->id)->sole()->status);
    }

    public function test_lobby_status_shows_rules_and_waiting_count(): void
    {
        $player = $this->player('Rina');
        $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
        $game = Game::sole();

        $html = $this->get(route('poin.lobby.status', $game))->assertOk()->json('html');

        $this->assertStringContainsString('Giliran berjalan searah jarum jam: P1, P2, P3, lalu P4.', $html);
        $this->assertStringContainsString('Menunggu semua pemain Ready (0/4)', $html);
        $this->assertStringContainsString('Kamu', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_game_cannot_start_until_all_four_are_ready(): void
    {
        $player = $this->player('Rina');
        $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
        $game = Game::sole();

        $this->post(route('poin.lobby.start', $game))->assertRedirect(route('poin.lobby', $game));

        $this->assertSame(Game::STATUS_LOBBY, $game->fresh()->status);
    }

    public function test_any_seated_player_starts_a_full_table_and_others_are_redirected_by_polling(): void
    {
        $game = $this->fullLobby();
        $last = $game->seats->last()->player;

        $html = $this->as($last)->get(route('poin.lobby.status', $game))->json('html');
        $this->assertStringContainsString('Semua pemain siap (4/4)', $html);

        $this->post(route('poin.lobby.start', $game))->assertRedirect(route('poin.game', $game));
        $this->assertSame(Game::STATUS_PLAYING, $game->fresh()->status);
        $this->assertNotNull($game->fresh()->started_at);

        $this->as($game->seats->first()->player)->getJson(route('poin.lobby.status', $game))
            ->assertJson(['redirect' => route('poin.game', $game)]);
    }

    public function test_game_page_shows_position_and_win_button(): void
    {
        $game = $this->fullLobby();
        $seat = $game->seats->get(1);
        app(LobbyService::class)->start($game);

        $this->as($seat->player)->get(route('poin.game', $game))->assertOk()->assertSee('Meja 1');

        $html = $this->get(route('poin.game.status', $game))->json('html');

        foreach (['RONDE 1', 'POSISI PEMAIN', 'Kamu · P2', 'SAYA MENANG', 'Tekan tombol saat kamu menang', 'Tombol aktif lagi setelah tersambung.'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_table_in_play_refuses_new_players(): void
    {
        $game = $this->fullLobby();
        app(LobbyService::class)->start($game);

        $this->as($this->player('Lima'));

        $this->get(route('poin.table.content', $this->table->qr_code))->assertSee('Sedang dimainkan');
        $this->post(route('poin.table.join', $this->table->qr_code))->assertRedirect(route('poin.table', $this->table->qr_code));
        $this->assertSame(4, $game->seats()->count());
    }

    public function test_seated_player_scanning_again_returns_to_their_game(): void
    {
        $game = $this->fullLobby();
        $seat = $game->seats->first();
        app(LobbyService::class)->start($game);

        $this->as($seat->player)->get(route('poin.scan'))->assertRedirect(route('poin.game', $game));
    }

    public function test_leaving_the_lobby_frees_the_seat_and_closes_an_empty_lobby(): void
    {
        $player = $this->player('Rina');
        $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
        $game = Game::sole();

        $this->post(route('poin.lobby.leave', $game))->assertRedirect(route('poin.scan'));

        $this->assertSame(0, GamePlayer::count());
        $this->assertSame(Game::STATUS_FINISHED, $game->fresh()->status);
    }

    public function test_inactive_players_lose_their_seat_after_ninety_seconds(): void
    {
        $idle = $this->player('Diam');
        $this->as($idle)->post(route('poin.table.join', $this->table->qr_code));
        $game = Game::sole();

        $this->travel(LobbyService::STALE_SECONDS + 1)->seconds();

        $this->as($this->player('Baru'))->post(route('poin.table.join', $this->table->qr_code));

        $this->assertFalse(GamePlayer::where('player_id', $idle->id)->exists());
    }

    public function test_only_seated_players_can_open_a_game(): void
    {
        $game = $this->fullLobby();

        $this->as($this->player('Asing'));

        $this->get(route('poin.lobby', $game))->assertForbidden();
        $this->get(route('poin.lobby.status', $game))->assertForbidden();
        $this->post(route('poin.lobby.start', $game))->assertForbidden();
        $this->get(route('poin.game', $game))->assertForbidden();
    }

    public function test_dashboard_lists_table_qr_codes_for_table_managers_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => []]);

        $this->actingAs($admin)->get(route('dashboard.tables.qr'))->assertOk()
            ->assertSee($this->table->qr_code)
            ->assertSee(config('app.points_domain') . '/meja/' . $this->table->qr_code);

        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true, 'permissions' => []]);

        Auth::forgetGuards();

        $this->actingAs($staff)->get(route('dashboard.tables.qr'))->assertForbidden();
    }
}
