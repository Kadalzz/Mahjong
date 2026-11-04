<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use App\Services\Poin\LeaderboardService;
use App\Services\Poin\LobbyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PoinRoundFlowTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;
    private Game $game;
    private array $players = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-11-03 10:00:00');
        $this->table = MahjongTable::create(['name' => 'Meja 1 - Reguler', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1]);

        foreach (['Satu', 'Dua', 'Tiga', 'Empat'] as $name) {
            $player = Player::create(['nickname' => $name, 'email' => strtolower($name) . '@example.com', 'password' => 'Rahasia123']);
            $this->players[] = $player;
            $this->as($player)->post(route('poin.table.join', $this->table->qr_code));
            $this->post(route('poin.lobby.ready', Game::sole()), ['ready' => 1]);
        }

        $this->game = Game::sole();
        app(LobbyService::class)->start($this->game);
    }

    private function as(Player $player): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->withSession([Player::SESSION_FINGERPRINT => $player->passwordFingerprint()]);

        return $this->actingAs($player, 'player');
    }

    private function win(int $seat, string $from, ?int $round = null)
    {
        return $this->as($this->players[$seat - 1])->postJson(route('poin.round.store', $this->game), [
            'ronde' => $round ?? $this->game->fresh()->current_round,
            'dari'  => $from,
        ]);
    }

    private function pointsBySeat(): array
    {
        return collect($this->players)
            ->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => (int) PlayerRoundScore::where('player_id', $player->id)->sum('points')])
            ->all();
    }

    public function test_win_screen_shows_the_figma_texts_and_the_other_players(): void
    {
        $this->as($this->players[0])->get(route('poin.round.win', $this->game))->assertOk()
            ->assertSee('Kamu Menang!')
            ->assertSee('Pilih dari mana kamu menang: dari pemain lain atau dari deck.')
            ->assertSee('Pilih Sumber Kemenangan')
            ->assertSee('SATU MENANG DARI')
            ->assertSee('Win From')
            ->assertSee('Deck')
            ->assertSee('Dua')
            ->assertSee('Tiga')
            ->assertSee('Empat')
            ->assertSee('MENYIMPAN POIN…')
            ->assertSee('Poin belum tersimpan')
            ->assertSee('Coba Simpan Lagi');
    }

    public function test_win_from_a_player_scores_four_minus_two_and_minus_one(): void
    {
        $this->win(1, '2')->assertOk()->assertJson(['redirect' => route('poin.round.result', $this->game)]);

        $this->assertSame([1 => 4, 2 => -2, 3 => -1, 4 => -1], $this->pointsBySeat());
        $this->assertSame(2, $this->game->fresh()->current_round);
        $this->assertSame(4, PlayerRoundScore::count());
        $this->assertSame(1, PlayerRoundScore::where('is_winner', true)->count());

        $this->get(route('poin.round.result', $this->game))->assertOk()
            ->assertSee('Poin Menang')
            ->assertSee('RONDE 1')
            ->assertSee('KAMU MENANG · DARI DUA')
            ->assertSee('Kamu')
            ->assertSee('+4')
            ->assertSee('Lanjutkan Permainan')
            ->assertSee('Berhenti');
    }

    public function test_win_from_the_deck_scores_six_and_minus_two_each(): void
    {
        $this->win(3, 'deck')->assertOk();

        $this->assertSame([1 => -2, 2 => -2, 3 => 6, 4 => -2], $this->pointsBySeat());
        $this->as($this->players[2])->get(route('poin.round.result', $this->game))->assertSee('KAMU MENANG · DARI DECK');
    }

    public function test_other_players_are_sent_to_the_lose_screen_by_polling(): void
    {
        $this->win(1, 'deck');

        $this->as($this->players[1])->getJson(route('poin.game.status', $this->game))
            ->assertJson(['redirect' => route('poin.round.result', $this->game)]);

        $this->get(route('poin.round.result', $this->game))->assertOk()
            ->assertSee('Yah, Kamu Kalah')
            ->assertSee('Satu menang dari Deck. Poinmu berkurang −2.')
            ->assertSee('Dua · Kamu')
            ->assertDontSee('P2 · Kamu')
            ->assertSee('+6');
    }

    public function test_the_discarder_reads_that_the_win_came_from_them(): void
    {
        $this->win(1, '3');

        $this->as($this->players[2])->get(route('poin.round.result', $this->game))
            ->assertSee('Satu menang dari kamu. Poinmu berkurang −2.');
        $this->as($this->players[3])->get(route('poin.round.result', $this->game))
            ->assertSee('Satu menang dari Tiga. Poinmu berkurang −1.');
    }

    public function test_a_round_is_only_recorded_once(): void
    {
        $this->win(1, '2');
        $this->win(4, 'deck', round: 1)->assertOk()->assertJson(['redirect' => route('poin.round.result', $this->game)]);
        $this->win(1, '2', round: 1)->assertOk();

        $this->assertSame(1, GameRound::count());
        $this->assertSame(4, PlayerRoundScore::count());
    }

    public function test_a_winner_cannot_win_from_themselves(): void
    {
        $this->win(2, '2')->assertStatus(422);
        $this->win(2, '9')->assertStatus(422);

        $this->assertSame(0, GameRound::count());
    }

    public function test_continuing_returns_to_the_next_round_with_the_new_total(): void
    {
        $this->win(1, '2');

        $this->postJson(route('poin.round.continue', $this->game))->assertJson(['redirect' => route('poin.game', $this->game)]);

        $html = $this->getJson(route('poin.game.status', $this->game))->json('html');
        $this->assertStringContainsString('RONDE 2', $html);
        $this->assertStringContainsString('4 poin', $html);
    }

    public function test_the_recorder_can_undo_the_last_round(): void
    {
        $this->win(1, '2');

        $this->as($this->players[1])->post(route('poin.round.undo', $this->game), ['ronde' => 1]);
        $this->assertSame(1, GameRound::count());

        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 1])->assertRedirect(route('poin.game', $this->game));

        $this->assertSame(0, GameRound::count());
        $this->assertSame(0, PlayerRoundScore::count());
        $this->assertSame(1, $this->game->fresh()->current_round);

        $this->as($this->players[1])->getJson(route('poin.round.result.status', ['game' => $this->game, 'ronde' => 1]))
            ->assertJson(['redirect' => route('poin.game', $this->game)]);
    }

    public function test_only_the_last_round_can_be_undone(): void
    {
        $this->win(1, '2');
        foreach ($this->players as $player) {
            $this->as($player)->post(route('poin.round.continue', $this->game));
        }
        $this->win(2, 'deck');

        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 1]);
        $this->assertSame(2, GameRound::count());

        $this->as($this->players[1])->post(route('poin.round.undo', $this->game), ['ronde' => 2]);
        $this->assertSame([1], GameRound::pluck('number')->all());
        $this->assertSame([1 => 4, 2 => -2, 3 => -1, 4 => -1], $this->pointsBySeat());
    }

    public function test_stopping_ends_the_session_for_everyone_and_keeps_finished_rounds(): void
    {
        $this->win(1, '2');

        $this->as($this->players[3])->postJson(route('poin.round.stop', $this->game))
            ->assertJson(['redirect' => route('poin.round.summary', $this->game)]);

        $this->assertSame(Game::STATUS_FINISHED, $this->game->fresh()->status);
        $this->assertSame(4, PlayerRoundScore::count());

        $this->as($this->players[0])->getJson(route('poin.game.status', $this->game))
            ->assertJson(['redirect' => route('poin.round.summary', $this->game)]);

        $this->get(route('poin.round.summary', $this->game))->assertOk()
            ->assertSee('SESI SELESAI')
            ->assertSee('Terima kasih sudah bermain')
            ->assertSee('Poin kamu sudah tercatat di leaderboard Chicken Hand.')
            ->assertSee('+4')
            ->assertSee('1x')
            ->assertSee('1 main · 1 menang')
            ->assertSee('Lihat Leaderboard')
            ->assertSee('Ke Beranda');
    }

    public function test_finished_players_can_join_a_new_table(): void
    {
        $this->as($this->players[0])->post(route('poin.round.stop', $this->game));

        $this->assertNull(app(LobbyService::class)->activeSeatFor($this->players[0]));
        $this->get(route('poin.scan'))->assertOk();
    }

    public function test_logged_in_leaderboard_pins_your_row_with_your_movement(): void
    {
        $leader = Player::create(['nickname' => 'Juara', 'email' => 'juara@example.com', 'password' => 'Rahasia123']);
        PlayerRoundScore::create(['player_id' => $leader->id, 'points' => 3, 'is_winner' => true, 'played_at' => now()->subDay()]);
        PlayerRoundScore::create(['player_id' => $this->players[0]->id, 'points' => 1, 'is_winner' => false, 'played_at' => now()->subDay()]);

        $this->win(1, 'deck');

        $row = app(LeaderboardService::class)->playerRow($this->players[0]);
        $this->assertSame(1, $row->rank);
        $this->assertSame(1, $row->movement);

        $this->as($this->players[0])->get(route('poin.leaderboard.content'))->assertOk()
            ->assertSee('Kamu')
            ->assertSee('Naik 1 posisi · 2 main · 1 menang');

        $this->get(route('poin.leaderboard'))->assertSee('aria-label="Profil"', false);
    }

    public function test_a_signed_in_player_without_points_still_sees_their_row(): void
    {
        $this->win(1, 'deck');
        $newcomer = Player::create(['nickname' => 'Baru', 'email' => 'baru@example.com', 'password' => 'Rahasia123']);

        $this->as($newcomer)->get(route('poin.leaderboard.content'))->assertOk()
            ->assertSee('Kamu')
            ->assertSee('Belum ada poin di periode ini')
            ->assertDontSee('Scan Meja');
    }

    public function test_logged_in_empty_leaderboard_offers_a_table_scan(): void
    {
        $this->as($this->players[0])->get(route('poin.leaderboard.content'))->assertOk()
            ->assertSee('Belum ada poin musim ini')
            ->assertSee('Scan Meja');
    }

    public function test_undoing_twice_does_not_remove_the_round_before(): void
    {
        $this->win(1, '2');
        foreach ($this->players as $player) {
            $this->as($player)->post(route('poin.round.continue', $this->game));
        }
        $this->win(1, 'deck');

        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 2]);
        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 2]);

        $this->assertSame([1], GameRound::pluck('number')->all());

        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 1]);

        $this->assertSame([1], GameRound::pluck('number')->all());
        $this->assertSame([1 => 4, 2 => -2, 3 => -1, 4 => -1], $this->pointsBySeat());
    }

    public function test_undo_needs_the_round_number_and_the_button_hides_once_used(): void
    {
        $this->win(1, '2');
        foreach ($this->players as $player) {
            $this->as($player)->post(route('poin.round.continue', $this->game));
        }
        $this->win(1, 'deck');

        $this->as($this->players[0])->post(route('poin.round.undo', $this->game))->assertSessionHasErrors('ronde');
        $this->assertSame(2, GameRound::count());

        $this->get(route('poin.round.result', $this->game))->assertSee('name="ronde" value="2"', false);
        $this->post(route('poin.round.undo', $this->game), ['ronde' => 2]);

        $this->get(route('poin.round.result', $this->game))->assertOk()->assertDontSee('Batalkan hasil ronde ini');
    }

    public function test_a_round_recorded_again_after_undo_can_be_undone(): void
    {
        $this->win(1, '2');
        $this->as($this->players[0])->post(route('poin.round.undo', $this->game), ['ronde' => 1]);
        $this->win(3, 'deck');

        $this->as($this->players[2])->post(route('poin.round.undo', $this->game), ['ronde' => 1]);

        $this->assertSame(0, GameRound::count());
        $this->assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 0], $this->pointsBySeat());
    }

    public function test_only_seated_players_can_touch_the_round(): void
    {
        $stranger = Player::create(['nickname' => 'Asing', 'email' => 'asing@example.com', 'password' => 'Rahasia123']);
        $this->as($stranger);

        $this->get(route('poin.round.win', $this->game))->assertForbidden();
        $this->postJson(route('poin.round.store', $this->game), ['ronde' => 1, 'dari' => 'deck'])->assertForbidden();
        $this->post(route('poin.round.stop', $this->game))->assertForbidden();
        $this->get(route('poin.round.summary', $this->game))->assertForbidden();
        $this->get(route('poin.round.result', $this->game))->assertForbidden();
        $this->getJson(route('poin.round.result.status', ['game' => $this->game, 'ronde' => 1]))->assertForbidden();
        $this->getJson(route('poin.round.win.status', ['game' => $this->game, 'ronde' => 1]))->assertForbidden();
        $this->post(route('poin.round.continue', $this->game))->assertForbidden();
        $this->post(route('poin.round.undo', $this->game), ['ronde' => 1])->assertForbidden();
        $this->assertSame(Game::STATUS_PLAYING, $this->game->fresh()->status);
    }
}
