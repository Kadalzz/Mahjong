<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_pagination_uses_bootstrap_and_indonesian_labels(): void
    {
        app()->setLocale('id');
        config(['app.fallback_locale' => 'id']);
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 35; $i++) {
            ActivityLog::record($admin, 'account_create', detail: "akun {$i}");
        }

        $this->actingAs($admin)->get(route('dashboard.activity'))->assertOk()
            ->assertSee('page-link', false)
            ->assertSee('Berikutnya')
            ->assertSee('Menampilkan')
            ->assertDontSee('pagination.next')
            ->assertDontSee('pagination.previous')
            ->assertDontSee('<svg', false);
    }
}
