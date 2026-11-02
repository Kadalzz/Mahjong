<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlayerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge(['nickname' => 'Rina', 'email' => 'rina@example.com', 'password' => 'Rahasia123'], $overrides);
    }

    private function makePlayer(array $overrides = []): Player
    {
        return Player::create($this->registerPayload($overrides));
    }

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true, 'permissions' => []]);
    }

    public function test_screens_show_the_figma_texts(): void
    {
        $this->get(route('poin.register'))->assertOk()
            ->assertSee('Buat Akun')
            ->assertSee('Poin kamu tercatat di leaderboard sepanjang tahun.')
            ->assertSee('NAMA PEMAIN *')
            ->assertSee('EMAIL *')
            ->assertSee('KATA SANDI *')
            ->assertSee('Sudah punya akun? Masuk');

        $this->get(route('poin.login'))->assertOk()
            ->assertSee('Masuk untuk mulai bermain dan mengumpulkan poin.')
            ->assertSee('EMAIL / NAMA PEMAIN *')
            ->assertSee('Belum punya akun? Daftar');
    }

    public function test_player_can_register_without_touching_the_staff_table(): void
    {
        $this->post(route('poin.register'), $this->registerPayload())->assertRedirect(route('poin.home'));

        $player = Player::sole();
        $this->assertSame('Rina', $player->nickname);
        $this->assertTrue(Hash::check('Rahasia123', $player->password));
        $this->assertSame(0, User::count());
        $this->assertTrue(Auth::guard('player')->check());
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_javascript_form_gets_a_json_redirect(): void
    {
        $this->postJson(route('poin.register'), $this->registerPayload())
            ->assertOk()
            ->assertJson(['redirect' => route('poin.home')]);
    }

    public function test_nickname_with_at_sign_is_rejected(): void
    {
        $this->postJson(route('poin.register'), $this->registerPayload(['nickname' => 'ri@na']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nickname');
    }

    public function test_nickname_is_limited_to_fifteen_characters(): void
    {
        $this->postJson(route('poin.register'), $this->registerPayload(['nickname' => str_repeat('a', 16)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nickname');

        $this->postJson(route('poin.register'), $this->registerPayload(['nickname' => str_repeat('a', 15)]))->assertOk();
    }

    public function test_password_needs_letters_a_number_and_an_uppercase_letter(): void
    {
        foreach (['Ab1', 'rahasia123', 'RahasiaSaja', '12345678', 'RAHASIA123x'] as $i => $password) {
            $response = $this->postJson(route('poin.register'), $this->registerPayload(['password' => $password]));

            if ($password === 'RAHASIA123x') {
                $response->assertOk();
            } else {
                $response->assertStatus(422)->assertJsonValidationErrors('password');
            }
        }
    }

    public function test_admin_reset_follows_the_same_password_rule(): void
    {
        $player = $this->makePlayer();

        $this->actingAs($this->staff())
            ->put(route('dashboard.players.update', $player), ['password' => 'lemah1234'])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Rahasia123', $player->fresh()->password));
    }

    public function test_duplicate_nickname_is_rejected_regardless_of_case(): void
    {
        $this->makePlayer();

        $this->postJson(route('poin.register'), $this->registerPayload(['nickname' => 'RINA', 'email' => 'lain@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nickname_key');

        $this->assertSame(1, Player::count());
    }

    public function test_duplicate_email_is_rejected_regardless_of_case(): void
    {
        $this->makePlayer();

        $this->postJson(route('poin.register'), $this->registerPayload(['nickname' => 'Lain', 'email' => 'RINA@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_with_nickname_in_any_case(): void
    {
        $player = $this->makePlayer();

        $this->post(route('poin.login'), ['login' => 'rInA', 'password' => 'Rahasia123'])->assertRedirect(route('poin.home'));

        $this->assertAuthenticatedAs($player, 'player');
    }

    public function test_login_with_email(): void
    {
        $player = $this->makePlayer();

        $this->post(route('poin.login'), ['login' => 'RINA@example.com', 'password' => 'Rahasia123'])->assertRedirect(route('poin.home'));

        $this->assertAuthenticatedAs($player, 'player');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makePlayer();

        $this->postJson(route('poin.login'), ['login' => 'Rina', 'password' => 'salah12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('login');

        $this->assertGuest('player');
    }

    public function test_login_is_throttled_per_ip(): void
    {
        $this->makePlayer();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('poin.login'), ['login' => 'Rina', 'password' => 'salah12345'])->assertStatus(422);
        }

        $this->postJson(route('poin.login'), ['login' => 'Rina', 'password' => 'salah12345'])->assertStatus(429);
    }

    public function test_registration_is_throttled_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('poin.register'), [])->assertStatus(422);
        }

        $this->postJson(route('poin.register'), [])->assertStatus(429);
    }

    public function test_guest_is_sent_to_the_login_screen(): void
    {
        $this->get(route('poin.home'))->assertRedirect(route('poin.login'));
    }

    public function test_logged_in_player_skips_the_login_screen(): void
    {
        $this->post(route('poin.login'), ['login' => $this->makePlayer()->email, 'password' => 'Rahasia123']);

        $this->get(route('poin.login'))->assertRedirect(route('poin.home'));
    }

    public function test_player_can_log_out(): void
    {
        $this->post(route('poin.login'), ['login' => $this->makePlayer()->email, 'password' => 'Rahasia123']);

        $this->post(route('poin.logout'))->assertRedirect(route('poin.login'));

        $this->assertGuest('player');
    }

    public function test_player_cannot_open_the_staff_dashboard(): void
    {
        $this->post(route('poin.login'), ['login' => $this->makePlayer()->email, 'password' => 'Rahasia123']);

        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.players.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_player_pages(): void
    {
        $this->actingAs($this->staff())->get(route('poin.home'))->assertRedirect(route('poin.login'));
    }

    public function test_staff_credentials_do_not_log_in_as_a_player(): void
    {
        $staff = $this->staff();

        $this->postJson(route('poin.login'), ['login' => $staff->email, 'password' => 'password'])->assertStatus(422);

        $this->assertGuest('player');
    }

    public function test_player_routes_only_exist_on_the_points_domain(): void
    {
        $this->get('http://' . parse_url(config('app.url'), PHP_URL_HOST) . '/masuk')->assertNotFound();
    }

    public function test_admin_can_reset_a_player_password_and_old_sessions_end(): void
    {
        $player = $this->makePlayer();
        $this->post(route('poin.login'), ['login' => $player->email, 'password' => 'Rahasia123']);
        $this->get(route('poin.home'))->assertOk();

        $admin = $this->staff();
        $this->actingAs($admin)->get(route('dashboard.players.index'))->assertOk()->assertSee($player->email);
        $this->get(route('dashboard.players.edit', $player))->assertOk();
        $this->put(route('dashboard.players.update', $player), ['password' => 'Barubaru123'])
            ->assertRedirect(route('dashboard.players.index'));

        $this->assertTrue(Hash::check('Barubaru123', $player->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'player_password_reset']);

        Auth::forgetGuards();

        $this->get(route('poin.home'))->assertRedirect(route('poin.login'));
    }

    public function test_only_admin_can_open_the_player_reset_pages(): void
    {
        $player = $this->makePlayer();

        $this->actingAs($this->staff('staff'))
            ->put(route('dashboard.players.update', $player), ['password' => 'Barubaru123'])
            ->assertForbidden();

        $this->assertTrue(Hash::check('Rahasia123', $player->fresh()->password));
    }
}
