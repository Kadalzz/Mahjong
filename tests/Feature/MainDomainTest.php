<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMainDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MainDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_main_site_routes_answer_on_any_host_while_the_main_domain_is_empty(): void
    {
        config(['app.main_domain' => null]);

        $this->get('http://localhost/pesan')->assertOk();
        $this->get('http://127.0.0.1/login')->assertOk();
        $this->get('http://poin.test/pesan')->assertOk();
    }

    public function test_main_site_routes_answer_only_on_the_main_domain_and_its_www(): void
    {
        config(['app.main_domain' => 'hzmahjong.test']);

        foreach (['/', '/pesan', '/jadwal', '/cek-booking', '/login'] as $path) {
            $this->get('http://hzmahjong.test' . $path)->assertOk();
            $this->get('http://www.hzmahjong.test' . $path)->assertOk();
            $this->get(($path === '/' ? 'http://lain.test' : 'http://poin.test') . $path)->assertNotFound();
        }

        $this->get('http://hzmahjong.test/dashboard')->assertRedirect();
        $this->get('http://poin.test/dashboard')->assertNotFound();
        $this->get('http://poin.test/')->assertOk();
        $this->get('http://poin.test/leaderboard')->assertOk();
    }

    public function test_payment_and_ayo_webhooks_keep_working_on_the_main_domain_only(): void
    {
        config(['app.main_domain' => 'hzmahjong.test']);

        foreach (['/doku/webhook', '/ayo/webhook'] as $path) {
            $main = $this->postJson('http://hzmahjong.test' . $path, []);
            $this->assertNotSame(404, $main->status(), $path);
            $this->assertNotSame(419, $main->status(), $path);
            $this->assertNotSame(500, $main->status(), $path);

            $this->postJson('http://poin.test' . $path, [])->assertNotFound();
        }
    }

    public function test_every_non_point_web_route_is_guarded(): void
    {
        $unguarded = collect(Route::getRoutes()->getRoutes())
            ->reject(fn ($route) => str_starts_with((string) $route->getName(), 'poin.'))
            ->reject(fn ($route) => in_array($route->uri(), ['up', 'storage/{path}'], true))
            ->reject(fn ($route) => in_array(EnsureMainDomain::class, $route->gatherMiddleware(), true))
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unguarded);
    }

    public function test_main_domain_is_empty_by_default(): void
    {
        $this->assertStringContainsString("'main_domain' => env('MAIN_DOMAIN')", file_get_contents(config_path('app.php')));
        $this->assertMatchesRegularExpression('/^MAIN_DOMAIN=\r?$/m', file_get_contents(base_path('.env.example')));
    }
}
