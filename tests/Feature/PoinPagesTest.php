<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\PlayerRoundScore;
use App\Services\Poin\LeaderboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoinPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-11-04 12:00:00'));
    }

    private function player(string $nickname): Player
    {
        return Player::create(['nickname' => $nickname, 'email' => strtolower($nickname) . '@example.com', 'password' => 'Rahasia123']);
    }

    private function score(Player $player, int $points, string $at = '2026-11-04 10:00:00', bool $win = false): void
    {
        PlayerRoundScore::create(['player_id' => $player->id, 'points' => $points, 'is_winner' => $win, 'played_at' => $at]);
    }

    private function login(): Player
    {
        $player = $this->player('Rina');
        $this->post(route('poin.login'), ['login' => $player->email, 'password' => 'Rahasia123']);

        return $player;
    }

    public function test_landing_shell_shows_the_loading_state_and_is_public(): void
    {
        $this->get(route('poin.home'))->assertOk()
            ->assertSee('Memuat…')
            ->assertSee('Tidak ada koneksi')
            ->assertSee('Coba Lagi')
            ->assertSee(route('poin.home.content'));
    }

    public function test_landing_content_shows_figma_texts_and_the_top_three(): void
    {
        foreach (['Budi' => 212, 'Rina' => 187, 'Andi' => 161, 'Dewi' => 143] as $name => $points) {
            $this->score($this->player($name), $points, win: true);
        }

        $this->get(route('poin.home.content'))->assertOk()
            ->assertSee('Main mahjong di venue kami.')
            ->assertSee('LEADERBOARD CHICKEN HAND')
            ->assertSee('Lihat Leaderboard')
            ->assertSee('Main Sekarang')
            ->assertSee('Main hanya di meja venue · Poin tercatat sepanjang tahun')
            ->assertSeeInOrder(['Budi', 'Rina', 'Andi'])
            ->assertSee('1 main · 1 menang')
            ->assertDontSee('Dewi');
    }

    public function test_main_sekarang_goes_to_register_for_guests_and_games_for_players(): void
    {
        $this->get(route('poin.home.content'))->assertSee(route('poin.register'));

        $this->login();

        $this->get(route('poin.home.content'))->assertSee(route('poin.games'));
    }

    public function test_game_pages_need_a_player_login(): void
    {
        $this->get(route('poin.games'))->assertRedirect(route('poin.login'));
        $this->get(route('poin.games.content'))->assertRedirect(route('poin.login'));
        $this->get(route('poin.mcr'))->assertRedirect(route('poin.login'));
    }

    public function test_pilih_game_shows_its_states_and_content(): void
    {
        $this->login();

        $this->get(route('poin.games'))->assertOk()
            ->assertSee('Pilih Permainan')
            ->assertSee('Satu meja, empat pemain, satu pemenang.')
            ->assertSee('Gagal memuat permainan')
            ->assertSee('Server sedang bermasalah. Coba lagi beberapa saat.');

        $this->get(route('poin.games.content'))->assertOk()
            ->assertSee('CHICKEN HAND')
            ->assertSee('Poin sepanjang tahun · 4 pemain')
            ->assertSee('COMING SOON')
            ->assertSee(route('poin.mcr'));
    }

    public function test_mcr_is_only_a_coming_soon_page(): void
    {
        $this->login();

        $this->get(route('poin.mcr'))->assertOk()
            ->assertSee('COMING SOON')
            ->assertSee('Permainan MCR sedang disiapkan. Nantikan segera di Hóng Zhōng Mahjong.')
            ->assertSee('Kembali ke Pilih Game');
    }

    public function test_public_leaderboard_needs_no_login_and_orders_by_points_then_wins(): void
    {
        $tieWithMoreWins = $this->player('Budi');
        $this->score($tieWithMoreWins, 6, win: true);
        $this->score($tieWithMoreWins, 4, win: true);
        $this->score($this->player('Rina'), 10, win: true);
        $this->score($this->player('Andi'), 20);
        $this->score($this->player('Dewi'), -2);

        $ranking = app(LeaderboardService::class)->ranking();

        $this->assertSame(['Andi', 'Budi', 'Rina', 'Dewi'], $ranking->pluck('nickname')->all());
        $this->assertSame([1, 2, 3, 4], $ranking->pluck('rank')->all());

        $this->get(route('poin.leaderboard'))->assertOk()
            ->assertSee('MUSIM 2026 · URUT POIN &amp; JUMLAH MENANG', false)
            ->assertSee('Leaderboard gagal dimuat');

        $this->get(route('poin.leaderboard.content'))->assertOk()
            ->assertSee('JUARA')
            ->assertSee('Dewi')
            ->assertSee('1 main · 0 menang')
            ->assertSee('Leaderboard diperbarui 2 jam lalu')
            ->assertSee('Masuk untuk ikut bermain');
    }

    public function test_tied_points_rank_by_more_wins_then_nickname_with_sequential_ranks(): void
    {
        $this->score($this->player('zaki'), 6, win: true);
        $this->score($this->player('Budi'), 6, win: true);
        $this->score($this->player('andi'), 6, win: true);
        $fewerWins = $this->player('Aaron');
        $this->score($fewerWins, 8);
        $this->score($fewerWins, -2);
        $moreWins = $this->player('Yuni');
        $this->score($moreWins, 4, win: true);
        $this->score($moreWins, 2, win: true);
        $this->score($this->player('Citra'), 7);

        foreach (range(1, 3) as $attempt) {
            $ranking = app(LeaderboardService::class)->ranking();

            $this->assertSame(['Citra', 'Yuni', 'andi', 'Budi', 'zaki', 'Aaron'], $ranking->pluck('nickname')->all());
            $this->assertSame([7, 6, 6, 6, 6, 6], $ranking->pluck('points')->all());
            $this->assertSame([1, 2, 3, 4, 5, 6], $ranking->pluck('rank')->all());
        }

        $this->get(route('poin.leaderboard.content'))->assertOk()->assertSeeInOrder(['pn-podium--2', 'Yuni', 'pn-podium--1', 'Citra', 'pn-podium--3', 'andi', 'Budi', 'zaki', 'Aaron'], false);
        $this->get(route('poin.home.content'))->assertOk()->assertSeeInOrder(['Citra', 'Yuni', 'andi'])->assertDontSee('Budi');
    }

    public function test_leaderboard_periods_only_count_scores_inside_the_period(): void
    {
        $thisWeek = $this->player('Minggu');
        $this->score($thisWeek, 5, '2026-11-02 09:00:00');

        $thisMonth = $this->player('Bulan');
        $this->score($thisMonth, 5, '2026-11-01 23:00:00');

        $thisYear = $this->player('Tahun');
        $this->score($thisYear, 5, '2026-10-01 09:00:00');

        $lastYear = $this->player('Lalu');
        $this->score($lastYear, 5, '2025-12-31 23:00:00');

        $service = app(LeaderboardService::class);

        $this->assertEqualsCanonicalizing(['Minggu'], $service->ranking('minggu')->pluck('nickname')->all());
        $this->assertEqualsCanonicalizing(['Minggu', 'Bulan'], $service->ranking('bulan')->pluck('nickname')->all());
        $this->assertEqualsCanonicalizing(['Minggu', 'Bulan', 'Tahun'], $service->ranking('tahun')->pluck('nickname')->all());

        $this->get(route('poin.leaderboard', ['periode' => 'bulan']))->assertSee('BULAN INI · URUT POIN', false)->assertDontSee('Tahun</p>', false);
        $this->get(route('poin.leaderboard', ['periode' => 'minggu']))->assertSee('MINGGU INI · URUT POIN', false);
        $this->get(route('poin.leaderboard.content', ['periode' => 'bulan']))->assertSee('Bulan')->assertDontSee('Tahun');
    }

    public function test_leaderboard_periods_cut_exactly_at_midnight_in_jakarta_time(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        foreach ([
            'SenNol'   => '2026-11-02 00:00:00',
            'MingAkh'  => '2026-11-01 23:59:59',
            'NovNol'   => '2026-11-01 00:00:00',
            'OktAkh'   => '2026-10-31 23:59:59',
            'JanNol'   => '2026-01-01 00:00:00',
            'DesLalu'  => '2025-12-31 23:59:59',
        ] as $name => $at) {
            $this->score($this->player($name), 5, $at);
        }

        $service = app(LeaderboardService::class);

        $this->assertEqualsCanonicalizing(['SenNol'], $service->ranking('minggu')->pluck('nickname')->all());
        $this->assertEqualsCanonicalizing(['SenNol', 'MingAkh', 'NovNol'], $service->ranking('bulan')->pluck('nickname')->all());
        $this->assertEqualsCanonicalizing(['SenNol', 'MingAkh', 'NovNol', 'OktAkh', 'JanNol'], $service->ranking('tahun')->pluck('nickname')->all());
        $this->assertEqualsCanonicalizing(['SenNol', 'MingAkh', 'NovNol', 'OktAkh', 'JanNol'], $service->ranking('lain')->pluck('nickname')->all());
    }

    public function test_public_pages_open_without_login_and_ignore_an_unknown_period(): void
    {
        foreach (['poin.home', 'poin.home.content', 'poin.leaderboard', 'poin.leaderboard.content', 'poin.help'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('poin.leaderboard', ['periode' => "'; DROP TABLE players; --"]))->assertOk()->assertSee('MUSIM 2026 · URUT POIN', false);
    }

    public function test_leaderboard_never_shows_player_emails(): void
    {
        $player = $this->player('Rina');
        $this->score($player, 5);

        $this->get(route('poin.leaderboard.content'))->assertDontSee($player->email);
        $this->get(route('poin.home.content'))->assertDontSee($player->email);
    }

    public function test_empty_leaderboard_explains_itself(): void
    {
        $this->get(route('poin.leaderboard.content'))->assertOk()
            ->assertSee('Belum ada poin musim ini')
            ->assertSee('Mainkan satu ronde di meja venue untuk masuk ke leaderboard.')
            ->assertSee('Masuk untuk ikut bermain');
    }
}
