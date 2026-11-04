<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\MahjongTable;
use App\Models\Player;
use App\Models\PlayerRoundScore;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoinDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $nickname, ?string $email = null): Player
    {
        return Player::create(['nickname' => $nickname, 'email' => $email ?? strtolower(preg_replace('/\W/', '', $nickname) ?: 'x') . '@example.com', 'password' => 'Rahasia123']);
    }

    public function test_a_player_with_recorded_rounds_cannot_be_deleted_and_the_others_keep_their_points(): void
    {
        $table = MahjongTable::create(['name' => 'Meja 1', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1]);
        $game = Game::create(['mahjong_table_id' => $table->id, 'status' => Game::STATUS_FINISHED]);
        $players = collect(['Satu', 'Dua', 'Tiga', 'Empat'])->map(fn ($name) => $this->player($name));
        $round = GameRound::create(['game_id' => $game->id, 'number' => 1, 'winner_player_id' => $players[0]->id, 'discarder_player_id' => $players[1]->id, 'recorded_by_player_id' => $players[2]->id]);

        foreach ([4, -2, -1, -1] as $i => $points) {
            PlayerRoundScore::create(['player_id' => $players[$i]->id, 'game_round_id' => $round->id, 'points' => $points, 'is_winner' => $i === 0, 'played_at' => now()]);
        }

        foreach ([0, 1, 2] as $i) {
            try {
                $players[$i]->delete();
                $this->fail("Pemain {$players[$i]->nickname} terhapus padahal tercatat di ronde.");
            } catch (QueryException) {
            }
        }

        $this->assertSame(4, Player::count());
        $this->assertSame(1, GameRound::count());
        $this->assertSame(4, PlayerRoundScore::where('game_round_id', $round->id)->count());
        $this->assertSame(-1, (int) PlayerRoundScore::where('player_id', $players[3]->id)->sum('points'));
    }

    public function test_a_player_without_rounds_can_still_be_deleted(): void
    {
        $player = $this->player('Baru');

        $player->delete();

        $this->assertSame(0, Player::count());
    }

    public function test_dashboard_player_search_treats_percent_and_underscore_literally(): void
    {
        foreach (['a_b', 'axb', '100%', '1000', 'Polos'] as $nickname) {
            $this->player($nickname);
        }

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => []]);
        $this->actingAs($admin);

        $found = fn (string $q) => $this->get(route('dashboard.players.index', ['q' => $q]))->assertOk()
            ->assertSeeHtml('value="' . e($q) . '"')
            ->getContent();
        $names = function (string $q) use ($found) {
            preg_match_all('#<td class="fw-600">(.*?)</td>#', $found($q), $matches);

            return array_map('html_entity_decode', $matches[1]);
        };

        $this->assertSame(['a_b'], $names('_'));
        $this->assertSame(['100%'], $names('%'));
        $this->assertSame(['a_b'], $names('A_B'));
        $this->assertSame(['Polos'], $names('pol'));
        $this->assertSame([], $names('!'));
        $this->assertSame(['100%', '1000'], $names('100'));
    }
}
