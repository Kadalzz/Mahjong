<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameRound;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class PoinIdleGamesTest extends TestCase
{
    use RefreshDatabase;

    private int $tables = 0;

    private function game(string $status, int $idleMinutes, ?int $seenMinutes = null): Game
    {
        $this->tables++;
        $table = MahjongTable::create(['name' => "Meja {$this->tables}", 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => $this->tables]);
        $game = Game::create(['mahjong_table_id' => $table->id, 'status' => $status, 'started_at' => $status === Game::STATUS_PLAYING ? now()->subHours(5) : null]);

        foreach (range(1, Game::SEATS) as $seat) {
            $player = Player::create(['nickname' => "P{$this->tables}x{$seat}", 'email' => "p{$this->tables}x{$seat}@example.com", 'password' => 'Rahasia123']);
            GamePlayer::create(['game_id' => $game->id, 'player_id' => $player->id, 'seat' => $seat, 'is_ready' => true, 'last_seen_at' => now()->subMinutes($seenMinutes ?? $idleMinutes)]);
        }

        Game::whereKey($game->id)->toBase()->update(['updated_at' => now()->subMinutes($idleMinutes)]);

        return $game->fresh();
    }

    public function test_playing_games_close_after_three_idle_hours_and_keep_their_rounds(): void
    {
        $idle = $this->game(Game::STATUS_PLAYING, 181);
        $seats = $idle->seats;
        $round = GameRound::create(['game_id' => $idle->id, 'number' => 1, 'winner_player_id' => $seats[0]->player_id, 'recorded_by_player_id' => $seats[0]->player_id]);

        foreach ([6, -2, -2, -2] as $i => $points) {
            PlayerRoundScore::create(['player_id' => $seats[$i]->player_id, 'game_round_id' => $round->id, 'points' => $points, 'is_winner' => $i === 0, 'played_at' => now()->subHours(4)]);
        }

        $active = $this->game(Game::STATUS_PLAYING, 181, 179);
        $recent = $this->game(Game::STATUS_PLAYING, 179);

        $this->artisan('poin:close-idle-games')->assertSuccessful();

        $this->assertSame(Game::STATUS_FINISHED, $idle->fresh()->status);
        $this->assertNotNull($idle->fresh()->ended_at);
        $this->assertSame(1, $idle->rounds()->count());
        $this->assertSame(0, (int) PlayerRoundScore::where('game_round_id', $round->id)->sum('points'));
        $this->assertSame(4, PlayerRoundScore::where('game_round_id', $round->id)->count());
        $this->assertSame(Game::STATUS_PLAYING, $active->fresh()->status);
        $this->assertSame(Game::STATUS_PLAYING, $recent->fresh()->status);
    }

    public function test_lobbies_close_after_forty_five_idle_minutes(): void
    {
        $idle = $this->game(Game::STATUS_LOBBY, 46);
        $seen = $this->game(Game::STATUS_LOBBY, 46, 44);
        $recent = $this->game(Game::STATUS_LOBBY, 44);

        $this->artisan('poin:close-idle-games')->assertSuccessful();

        $this->assertSame(Game::STATUS_FINISHED, $idle->fresh()->status);
        $this->assertSame(Game::STATUS_LOBBY, $seen->fresh()->status);
        $this->assertSame(Game::STATUS_LOBBY, $recent->fresh()->status);
    }

    public function test_a_closed_idle_game_frees_its_table_and_players(): void
    {
        $idle = $this->game(Game::STATUS_PLAYING, 200);
        $player = $idle->seats->first()->player;

        $this->artisan('poin:close-idle-games')->assertSuccessful();

        $this->withoutVite();
        $this->actingAs($player, 'player')
            ->withSession([Player::SESSION_FINGERPRINT => $player->passwordFingerprint()])
            ->post(route('poin.table.join', $idle->table->qr_code));

        $this->assertSame(Game::STATUS_LOBBY, Game::open()->where('mahjong_table_id', $idle->mahjong_table_id)->sole()->status);
    }

    public function test_idle_limits_come_from_config(): void
    {
        config(['poin.idle_minutes.playing' => 300, 'poin.idle_minutes.lobby' => 10]);

        $playing = $this->game(Game::STATUS_PLAYING, 200);
        $lobby = $this->game(Game::STATUS_LOBBY, 11);

        $this->artisan('poin:close-idle-games')->assertSuccessful();

        $this->assertSame(Game::STATUS_PLAYING, $playing->fresh()->status);
        $this->assertSame(Game::STATUS_FINISHED, $lobby->fresh()->status);
    }

    public function test_finished_games_are_left_alone_and_the_command_is_scheduled(): void
    {
        $game = $this->game(Game::STATUS_FINISHED, 500);
        Game::whereKey($game->id)->toBase()->update(['ended_at' => '2026-01-01 00:00:00']);

        $this->artisan('poin:close-idle-games')->assertSuccessful();

        $this->assertSame('2026-01-01 00:00:00', $game->fresh()->ended_at->format('Y-m-d H:i:s'));
        $this->assertTrue(collect(Schedule::events())->contains(fn ($event) => str_contains($event->command, 'poin:close-idle-games')));
    }
}
