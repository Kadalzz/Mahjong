<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleBookerNamesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-11-09 08:00:00');
        $table = MahjongTable::create(['name' => 'Meja 2 - Reguler', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 2]);

        Booking::create([
            'source' => 'web', 'mahjong_table_id' => $table->id, 'customer_name' => 'Danur', 'customer_phone' => '0812',
            'booking_date' => '2026-11-10', 'start_time' => '00:00', 'duration_hours' => 1, 'total_price' => 50000,
            'status' => 'active', 'booking_code' => 'T-1',
        ]);
        Booking::create([
            'source' => 'block', 'mahjong_table_id' => $table->id, 'customer_name' => 'Rahasia Blok', 'customer_phone' => '',
            'booking_date' => '2026-11-10', 'start_time' => '05:00', 'duration_hours' => 1, 'total_price' => 0,
            'status' => 'active', 'booking_code' => 'T-2',
        ]);
    }

    public function test_admins_see_who_booked_each_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('schedule.index', ['date' => '2026-11-10']))->assertOk()
            ->assertSee('Danur Dipesan 00:00–01:00')
            ->assertSee('<span class="booking-name">Danur</span> Dipesan 00:00–01:00', false)
            ->assertDontSee('Rahasia Blok');
    }

    public function test_guests_and_staff_do_not_see_names(): void
    {
        $this->get(route('schedule.index', ['date' => '2026-11-10']))->assertOk()
            ->assertSee('Sudah Dipesan 00:00–01:00')
            ->assertDontSee('Danur');

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('schedule.index', ['date' => '2026-11-10']))->assertOk()->assertDontSee('Danur');
    }

    public function test_cover_tagline_no_longer_mentions_beers(): void
    {
        $this->get('/')->assertOk()->assertSee('Mahjong — Open 24 Hours')->assertDontSee('Beers');
    }
}
