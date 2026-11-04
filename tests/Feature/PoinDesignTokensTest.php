<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PoinDesignTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function openDevPages(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['poin.dev_pages' => true]);
    }

    public function test_tokens_page_opens_in_the_local_environment_with_dev_pages_enabled(): void
    {
        $this->openDevPages();

        $this->get('/dev/tokens')
            ->assertOk()
            ->assertSee('SAYA MENANG')
            ->assertSee('CHICKEN HAND')
            ->assertSee('Belum ada poin musim ini')
            ->assertSee('brand-green-deep');
    }

    public function test_tokens_page_is_hidden_in_the_local_environment_when_dev_pages_are_disabled(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['poin.dev_pages' => false]);

        $this->get('/dev/tokens')->assertNotFound();
    }

    public function test_tokens_page_is_hidden_outside_the_local_environment_even_with_dev_pages_enabled(): void
    {
        foreach (['production', 'testing'] as $environment) {
            $this->app->detectEnvironment(fn () => $environment);

            foreach ([true, false] as $enabled) {
                config(['poin.dev_pages' => $enabled]);

                $this->get('/dev/tokens')->assertNotFound();
            }
        }
    }

    public function test_dev_pages_are_disabled_by_default(): void
    {
        $this->assertStringContainsString("env('POINTS_DEV_PAGES', false)", File::get(config_path('poin.php')));
        $this->assertMatchesRegularExpression('/^POINTS_DEV_PAGES=false\r?$/m', File::get(base_path('.env.example')));
    }

    public function test_every_figma_asset_used_by_a_component_exists(): void
    {
        $this->openDevPages();

        $html = $this->get('/dev/tokens')->getContent();

        preg_match_all('#/images/poin/[\w/-]+\.svg#', $html, $matches);

        $this->assertNotEmpty($matches[0]);

        foreach (array_unique($matches[0]) as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')));
            $this->assertGreaterThan(0, filesize(public_path(ltrim($path, '/'))));
        }
    }

    public function test_poin_blade_files_do_not_hardcode_colors_or_sizes(): void
    {
        $files = [resource_path('views/layouts/poin.blade.php')];

        foreach (['views/components/poin', 'views/dev', 'views/poin'] as $path) {
            foreach (File::allFiles(resource_path($path)) as $file) {
                $files[] = $file->getPathname();
            }
        }

        $this->assertGreaterThan(40, count($files));

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|rgba?\(|\d+(px|rem)\b/', File::get($file), $file);
        }
    }

    public function test_points_fonts_are_self_hosted_with_their_licences(): void
    {
        $layout = File::get(resource_path('views/layouts/poin.blade.php'));
        $css = File::get(resource_path('css/poin.css'));

        foreach ([$layout, $css] as $source) {
            $this->assertStringNotContainsString('fonts.googleapis.com', $source);
            $this->assertStringNotContainsString('fonts.gstatic.com', $source);
        }

        foreach (['baloo2-latin', 'baloo2-latin-ext', 'nunito-latin', 'nunito-latin-ext'] as $font) {
            $this->assertStringContainsString("/fonts/poin/{$font}.woff2", $css);
            $this->assertFileExists(public_path("fonts/poin/{$font}.woff2"));
        }

        $this->assertStringContainsString("fonts/poin/baloo2-latin.woff2') }}\" as=\"font\"", $layout);
        $this->assertSame(4, substr_count($css, 'font-display: swap;'));
        $this->assertFileExists(public_path('fonts/poin/OFL-Baloo2.txt'));
        $this->assertFileExists(public_path('fonts/poin/OFL-Nunito.txt'));
    }

    public function test_booking_pages_do_not_load_the_poin_stylesheet(): void
    {
        $this->assertStringNotContainsString('poin.css', File::get(resource_path('views/layouts/app.blade.php')));
        $this->assertStringNotContainsString('poin.css', File::get(resource_path('views/layouts/dashboard.blade.php')));
        $this->assertStringNotContainsString('poin', File::get(resource_path('css/app.css')));
    }
}
