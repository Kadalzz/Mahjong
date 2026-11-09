<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameRound;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use App\Services\Poin\LobbyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PoinClaimConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;
    private Game $game;
    private array $players = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-11-09 10:00:00');
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

    private function claim(int $seat, string $from): GameRound
    {
        $this->as($this->players[$seat - 1])->postJson(route('poin.round.store', $this->game), [
            'ronde' => $this->game->fresh()->current_round,
            'dari'  => $from,
        ])->assertJson(['redirect' => route('poin.round.claim', $this->game)]);

        return GameRound::sole();
    }

    private function statusFor(int $seat, GameRound $claim)
    {
        return $this->as($this->players[$seat - 1])->getJson(route('poin.round.claim.status', ['game' => $this->game, 'klaim' => $claim->id]));
    }

    private function agree(int $seat, GameRound $claim)
    {
        return $this->as($this->players[$seat - 1])->post(route('poin.round.claim.agree', $this->game), ['klaim' => $claim->id]);
    }

    public function test_a_win_waits_for_the_three_losers_before_points_are_recorded(): void
    {
        $claim = $this->claim(1, '2');

        $this->assertSame(0, PlayerRoundScore::count());
        $this->assertSame(1, $this->game->fresh()->current_round);

        foreach ([2, 3, 4] as $seat) {
            $this->as($this->players[$seat - 1])->getJson(route('poin.game.status', $this->game))
                ->assertJson(['redirect' => route('poin.round.claim', $this->game)]);
        }

        $html = $this->statusFor(2, $claim)->assertOk()->json('html');
        foreach (['KONFIRMASI KEMENANGAN', 'Satu menang dari kamu', 'Poinmu akan berkurang −2.', 'Otomatis setuju dalam 15 detik', 'Setuju', 'Tolak', 'Dua · Kamu'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        $html = $this->statusFor(3, $claim)->json('html');
        $this->assertStringContainsString('Satu menang dari Dua', $html);
        $this->assertStringContainsString('Poinmu akan berkurang −1.', $html);

        $this->agree(2, $claim);
        $this->agree(3, $claim);

        $html = $this->statusFor(1, $claim)->assertOk()->json('html');
        foreach (['MENUNGGU KONFIRMASI · 2/3', 'Kamu menang dari Dua', 'Belum membuka', 'Batalkan Klaim'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertSame(0, PlayerRoundScore::count());

        $this->agree(4, $claim)->assertRedirect(route('poin.round.result', $this->game));

        $this->assertSame(4, PlayerRoundScore::count());
        $this->assertNotNull($claim->fresh()->confirmed_at);
        $this->assertSame(2, $this->game->fresh()->current_round);
        $this->statusFor(1, $claim)->assertJson(['redirect' => route('poin.round.result', $this->game)]);
    }

    public function test_one_rejection_cancels_the_claim_and_tells_everyone_who_rejected(): void
    {
        $claim = $this->claim(1, 'deck');
        $this->agree(2, $claim);

        $this->as($this->players[2])->post(route('poin.round.claim.reject', $this->game), ['klaim' => $claim->id])
            ->assertRedirect(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]));

        $this->assertSame(0, GameRound::count());
        $this->assertSame(0, PlayerRoundScore::count());
        $this->assertSame(1, $this->game->fresh()->current_round);

        $this->statusFor(1, $claim)->assertJson(['redirect' => route('poin.game', ['game' => $this->game, 'klaim' => $claim->id])]);
        $this->as($this->players[0])->get(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]))->assertOk()
            ->assertSee('Klaim menangmu ditolak oleh Tiga.');
        $this->as($this->players[1])->get(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]))
            ->assertSee('Klaim menang Satu ditolak oleh Tiga.');
        $this->as($this->players[2])->get(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]))
            ->assertSee('Kamu menolak klaim menang Satu.');

        $this->claim(1, 'deck');
    }

    public function test_a_loser_who_saw_the_claim_agrees_automatically_after_fifteen_seconds(): void
    {
        $claim = $this->claim(1, '2');
        $this->agree(2, $claim);
        $this->agree(3, $claim);
        $this->statusFor(4, $claim)->assertSee('Otomatis setuju dalam 15 detik');

        $this->travel(14)->seconds();
        $this->statusFor(4, $claim)->assertSee('Otomatis setuju dalam 1 detik');
        $this->assertSame(0, PlayerRoundScore::count());

        $this->travel(1)->seconds();
        $this->statusFor(4, $claim)->assertJson(['redirect' => route('poin.round.result', $this->game)]);
        $this->assertSame([4, -2, -1, -1], collect($this->players)->map(fn ($p) => (int) PlayerRoundScore::where('player_id', $p->id)->sum('points'))->all());
    }

    public function test_the_fifteen_seconds_start_when_the_loser_opens_the_claim(): void
    {
        $claim = $this->claim(1, '2');
        $this->agree(2, $claim);
        $this->agree(3, $claim);

        $this->travel(20)->seconds();
        $this->statusFor(4, $claim)->assertSee('Otomatis setuju dalam 15 detik');

        $this->as($this->players[3])->post(route('poin.round.claim.reject', $this->game), ['klaim' => $claim->id]);
        $this->assertSame(0, GameRound::count());
    }

    public function test_a_loser_who_never_opens_the_claim_is_counted_after_thirty_seconds(): void
    {
        $claim = $this->claim(1, 'deck');
        $this->agree(2, $claim);
        $this->agree(3, $claim);

        $this->travel(29)->seconds();
        $this->statusFor(1, $claim)->assertSee('Belum membuka');
        $this->assertSame(0, PlayerRoundScore::count());

        $this->travel(1)->seconds();
        $this->statusFor(1, $claim)->assertJson(['redirect' => route('poin.round.result', $this->game)]);
        $this->assertSame(4, PlayerRoundScore::count());
    }

    public function test_rejecting_after_your_own_time_ran_out_does_nothing(): void
    {
        $claim = $this->claim(1, '2');
        $this->statusFor(2, $claim);
        $this->travel(16)->seconds();

        $this->as($this->players[1])->post(route('poin.round.claim.reject', $this->game), ['klaim' => $claim->id]);

        $this->assertSame(1, GameRound::count());
        $this->assertNull($claim->fresh()->confirmed_at);
    }

    public function test_the_winner_can_withdraw_but_cannot_confirm_or_reject_their_own_claim(): void
    {
        $claim = $this->claim(1, 'deck');

        $this->as($this->players[0])->post(route('poin.round.claim.reject', $this->game), ['klaim' => $claim->id]);
        $this->assertSame(1, GameRound::count());

        $this->as($this->players[1])->post(route('poin.round.claim.withdraw', $this->game), ['klaim' => $claim->id]);
        $this->assertSame(1, GameRound::count());

        $this->as($this->players[0])->post(route('poin.round.claim.withdraw', $this->game), ['klaim' => $claim->id])
            ->assertRedirect(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]));
        $this->assertSame(0, GameRound::count());

        $this->as($this->players[1])->get(route('poin.game', ['game' => $this->game, 'klaim' => $claim->id]))
            ->assertSee('Satu membatalkan klaim menang.');
    }

    public function test_a_second_win_while_a_claim_is_open_joins_the_open_claim(): void
    {
        $claim = $this->claim(1, 'deck');

        $this->as($this->players[2])->get(route('poin.round.win', $this->game))->assertRedirect(route('poin.round.claim', $this->game));
        $this->as($this->players[2])->postJson(route('poin.round.store', $this->game), ['ronde' => 1, 'dari' => 'deck']);

        $this->assertSame([$claim->id], GameRound::pluck('id')->all());
        $this->assertSame($this->players[0]->id, GameRound::sole()->winner_player_id);
    }

    public function test_stopping_the_session_drops_an_open_claim(): void
    {
        $claim = $this->claim(1, 'deck');

        $this->as($this->players[2])->post(route('poin.round.stop', $this->game));

        $this->assertSame(Game::STATUS_FINISHED, $this->game->fresh()->status);
        $this->assertSame(0, GameRound::count());
        $this->assertSame(0, PlayerRoundScore::count());
        $this->assertNull($claim->fresh());
    }

    public function test_only_seated_players_can_see_or_answer_a_claim(): void
    {
        $claim = $this->claim(1, 'deck');
        $stranger = Player::create(['nickname' => 'Asing', 'email' => 'asing@example.com', 'password' => 'Rahasia123']);
        $this->as($stranger);

        $this->get(route('poin.round.claim', $this->game))->assertForbidden();
        $this->getJson(route('poin.round.claim.status', ['game' => $this->game, 'klaim' => $claim->id]))->assertForbidden();
        $this->post(route('poin.round.claim.agree', $this->game), ['klaim' => $claim->id])->assertForbidden();
        $this->post(route('poin.round.claim.reject', $this->game), ['klaim' => $claim->id])->assertForbidden();
        $this->post(route('poin.round.claim.withdraw', $this->game), ['klaim' => $claim->id])->assertForbidden();

        $this->assertSame(1, GameRound::count());
    }

    public function test_claim_page_polls_every_second(): void
    {
        $this->claim(1, 'deck');

        $this->as($this->players[1])->get(route('poin.round.claim', $this->game))->assertOk()
            ->assertSee('data-poin-interval="1000"', false);
        $this->assertNotNull(GameRound::sole()->confirmations()->where('player_id', $this->players[1]->id)->value('seen_at'));
        $this->assertSame(['East', 'South', 'West', 'North'], array_values(GamePlayer::WINDS));
    }
}
