<?php

namespace Tests\Feature;

use App\Models\MahjongTable;
use App\Models\Pricing;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'doku.client_id'           => 'BRN-TEST',
            'doku.secret_key'          => 'SK-TEST',
            'doku.payment_due_minutes' => 5,
        ]);

        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => ['payment' => ['url' => 'https://checkout.test/pay']],
            ]),
        ]);

        $this->travelTo(Carbon::parse('2026-10-14 08:00:00'));

        $this->table = MahjongTable::create([
            'name' => 'Meja 1', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1,
        ]);
        Pricing::create(['mahjong_table_id' => $this->table->id, 'price_per_hour' => 50000, 'is_active' => true]);
    }

    private function asVisitor(string $sessionId): static
    {
        return $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);
    }

    private function check(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('booking.check', $this->table), [
            'booking_date' => '2026-10-14', 'start_time' => '20:00', 'duration_hours' => 1,
        ]);
    }

    public function test_thirty_visitors_on_the_same_shop_wifi_are_not_throttled(): void
    {
        $statuses = [];

        for ($visitor = 0; $visitor < 30; $visitor++) {
            $session = Str::random(40);
            for ($i = 0; $i < 5; $i++) {
                $statuses[] = $this->asVisitor($session)->check()->status();
            }
        }

        $this->assertNotContains(429, $statuses);
        $this->assertCount(150, $statuses);
    }

    public function test_thirty_visitors_can_each_place_a_booking_within_a_minute(): void
    {
        $statuses = [];

        for ($visitor = 0; $visitor < 30; $visitor++) {
            $statuses[] = $this->asVisitor(Str::random(40))->post(route('booking.store'), [
                'mahjong_table_id' => $this->table->id,
                'customer_name'    => "Tamu {$visitor}",
                'customer_phone'   => '081234567890',
                'booking_date'     => '2026-10-14',
                'start_time'       => sprintf('%02d:%02d', 9 + intdiv($visitor, 2), ($visitor % 2) * 30),
                'duration_hours'   => 1,
            ])->status();
        }

        $this->assertNotContains(429, $statuses);
    }

    public function test_status_polling_from_many_open_payment_pages_is_not_throttled(): void
    {
        $statuses = [];

        for ($visitor = 0; $visitor < 10; $visitor++) {
            $session = Str::random(40);
            for ($poll = 0; $poll < 15; $poll++) {
                $statuses[] = $this->asVisitor($session)->getJson('/booking/status/MJG-NONE' . $visitor)->status();
            }
        }

        $this->assertNotContains(429, $statuses);
    }

    public function test_a_single_visitor_spamming_is_still_throttled(): void
    {
        $session = Str::random(40);

        for ($i = 0; $i < 60; $i++) {
            $this->asVisitor($session)->check()->assertOk();
        }

        $this->asVisitor($session)->check()->assertStatus(429);
    }

    public function test_spoofed_forwarded_for_header_is_not_trusted(): void
    {
        $this->withHeader('X-Forwarded-For', '203.0.113.9')->get('/pesan');

        $this->assertSame('127.0.0.1', request()->ip());
    }
}
