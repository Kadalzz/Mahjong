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

    public function test_tokens_page_opens_in_the_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get('/dev/tokens')
            ->assertOk()
            ->assertSee('SAYA MENANG')
            ->assertSee('CHICKEN HAND')
            ->assertSee('Belum ada poin musim ini')
            ->assertSee('brand-green-deep');
    }

    public function test_tokens_page_is_hidden_outside_the_local_environment(): void
    {
        foreach (['production', 'testing'] as $environment) {
            $this->app->detectEnvironment(fn () => $environment);

            $this->get('/dev/tokens')->assertNotFound();
        }
    }

    public function test_every_figma_asset_used_by_a_component_exists(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

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
        $paths = [resource_path('views/components/poin'), resource_path('views/dev')];

        foreach ($paths as $path) {
            foreach (File::allFiles($path) as $file) {
                $content = $file->getContents();

                $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|rgba?\(|\d+(px|rem)\b/', $content, $file->getFilename());
            }
        }
    }

    public function test_booking_pages_do_not_load_the_poin_stylesheet(): void
    {
        $this->assertStringNotContainsString('poin.css', File::get(resource_path('views/layouts/app.blade.php')));
        $this->assertStringNotContainsString('poin.css', File::get(resource_path('views/layouts/dashboard.blade.php')));
        $this->assertStringNotContainsString('poin', File::get(resource_path('css/app.css')));
    }
}
