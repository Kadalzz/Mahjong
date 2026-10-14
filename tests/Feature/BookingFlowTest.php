<?php

namespace Tests\Feature;

use App\Jobs\ActivateTableDevice;
use App\Jobs\DeactivateTableDevice;
use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Services\TableDeviceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        config([
            'doku.client_id'           => 'BRN-TEST',
            'doku.secret_key'          => 'SK-TEST',
            'doku.is_production'       => false,
            'doku.payment_due_minutes' => 5,
        ]);

        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'response' => ['payment' => ['url' => 'https://checkout.test/pay']],
            ]),
        ]);

        $this->travelTo(Carbon::parse('2026-10-14 19:00:00'));

        $this->table = MahjongTable::create([
            'name'          => 'Meja 1 - Reguler',
            'capacity'      => 4,
            'status'        => 'available',
            'esp32_meja_id' => 1,
        ]);

        Pricing::create([
            'mahjong_table_id' => $this->table->id,
            'price_per_hour'   => 50000,
            'is_active'        => true,
        ]);
    }

    private function book(string $start, int $hours = 1, string $date = '2026-10-14')
    {
        return $this->post(route('booking.store'), [
            'mahjong_table_id' => $this->table->id,
            'customer_name'    => 'Tes',
            'customer_phone'   => '081234567890',
            'booking_date'     => $date,
            'start_time'       => $start,
            'duration_hours'   => $hours,
        ]);
    }

    private function existing(string $start, int $hours, string $status, ?Carbon $createdAt = null): Booking
    {
        $booking = Booking::create([
            'mahjong_table_id' => $this->table->id,
            'customer_name'    => 'Lain',
            'customer_phone'   => '081111111111',
            'booking_date'     => '2026-10-14',
            'start_time'       => $start,
            'duration_hours'   => $hours,
            'end_time'         => Booking::computeEndTime($start, $hours),
            'total_price'      => 50000 * $hours,
            'status'           => $status,
            'booking_code'     => Booking::generateCode(),
            'payment_order_id' => 'MJG-T-' . uniqid(),
        ]);

        if ($createdAt) {
            $booking->forceFill(['created_at' => $createdAt])->save();
        }

        return $booking;
    }

    private function sendWebhook(Booking $booking, string $status = 'SUCCESS')
    {
        $body = json_encode([
            'order'       => ['invoice_number' => $booking->payment_order_id, 'amount' => (int) $booking->total_price],
            'transaction' => ['status' => $status, 'original_request_id' => 'req-1'],
            'channel'     => ['id' => 'QRIS'],
        ]);

        $requestId = 'req-' . uniqid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $digest    = base64_encode(hash('sha256', $body, true));
        $toSign    = "Client-Id:BRN-TEST\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:/doku/webhook\nDigest:{$digest}";
        $signature = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $toSign, 'SK-TEST', true));

        return $this->call('POST', '/doku/webhook', [], [], [], [
            'CONTENT_TYPE'           => 'application/json',
            'HTTP_CLIENT_ID'         => 'BRN-TEST',
            'HTTP_REQUEST_ID'        => $requestId,
            'HTTP_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_SIGNATURE'         => $signature,
        ], $body);
    }

    public function test_slot_overlapping_an_existing_booking_is_rejected(): void
    {
        $this->existing('20:00', 1, 'active');

        $this->book('19:01')->assertSessionHasErrors('start_time');
        $this->assertSame(1, Booking::count());
    }

    public function test_slot_ending_exactly_when_next_booking_starts_is_allowed(): void
    {
        $this->existing('20:00', 1, 'active');

        $this->book('19:00')->assertRedirect();
        $this->book('21:00')->assertRedirect();

        $this->assertSame(2, Booking::where('status', 'pending_payment')->count());
    }

    public function test_flexible_start_time_ends_after_whole_hours(): void
    {
        $this->book('19:15', 2)->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame('21:15', substr($booking->end_time, 0, 5));
        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame('https://checkout.test/pay', $booking->payment_url);
    }

    public function test_start_time_in_the_past_is_rejected_but_play_now_grace_is_allowed(): void
    {
        $this->book('18:00')->assertSessionHasErrors('start_time');
        $this->book('18:56')->assertRedirect();
    }

    public function test_booking_can_run_past_midnight_and_blocks_the_next_day(): void
    {
        $this->book('23:30', 2)->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame('01:30', substr($booking->end_time, 0, 5));
        $this->assertSame('2026-10-15 01:30:00', $booking->ends_at->toDateTimeString());
        $this->assertTrue($booking->endsNextDay());

        $this->book('00:30', 1, '2026-10-15')->assertSessionHasErrors('start_time');
        $this->book('01:30', 1, '2026-10-15')->assertRedirect();
        $this->book('22:45', 1)->assertSessionHasErrors('start_time');
    }

    public function test_overnight_booking_shows_on_next_day_busy_list(): void
    {
        $this->existing('23:00', 3, 'active');

        $this->postJson(route('booking.check', $this->table), [
            'booking_date' => '2026-10-15', 'duration_hours' => 1,
        ])->assertJsonPath('busy.0.start', '00:00')->assertJsonPath('busy.0.end', '02:00');

        $this->get(route('schedule.index', ['date' => '2026-10-15']))->assertOk();
    }

    public function test_unpaid_hold_blocks_slot_until_it_expires(): void
    {
        $this->existing('20:00', 1, 'pending_payment', now()->subMinutes(2));
        $this->book('20:00')->assertSessionHasErrors('start_time');

        $this->travel(4)->minutes();
        $this->book('20:00')->assertRedirect();
        $this->assertSame(1, Booking::where('status', 'pending_payment')->where('created_at', '>', now()->subMinutes(5))->count());
    }

    public function test_expire_command_cancels_unpaid_bookings_after_hold(): void
    {
        $stale = $this->existing('20:00', 1, 'pending_payment', now()->subMinutes(6));
        $fresh = $this->existing('22:00', 1, 'pending_payment', now()->subMinutes(1));

        $this->artisan('app:expire-stale-bookings')->assertSuccessful();

        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('pending_payment', $fresh->fresh()->status);
    }

    public function test_failed_invoice_releases_the_slot(): void
    {
        config(['doku.secret_key' => '']);

        $this->book('20:00')->assertSessionHas('error');
        $this->assertSame('cancelled', Booking::latest('id')->first()->status);
    }

    public function test_paid_future_booking_schedules_the_table_to_turn_on_at_start(): void
    {
        Queue::fake();
        $device = $this->partialMock(TableDeviceService::class);
        $device->shouldNotReceive('turnOn');

        $booking = $this->existing('22:00', 1, 'pending_payment');

        $this->sendWebhook($booking)->assertOk();

        $this->assertSame('active', $booking->fresh()->status);
        Queue::assertPushed(ActivateTableDevice::class);
        Queue::assertNotPushed(DeactivateTableDevice::class);
    }

    public function test_paid_booking_starting_now_turns_on_and_schedules_off_at_slot_end(): void
    {
        Queue::fake();
        $device = $this->partialMock(TableDeviceService::class);
        $device->shouldReceive('turnOn')->once()->andReturn(true);

        $booking = $this->existing('19:00', 1, 'pending_payment');

        $this->sendWebhook($booking)->assertOk();

        $this->assertSame('active', $booking->fresh()->status);
        Queue::assertPushed(DeactivateTableDevice::class);
    }

    public function test_late_payment_for_taken_slot_is_flagged_for_manual_refund(): void
    {
        Queue::fake();
        $late  = $this->existing('20:00', 1, 'pending_payment', now()->subMinutes(10));
        $other = $this->existing('20:00', 1, 'active');

        $this->sendWebhook($late)->assertOk();

        $late->refresh();
        $this->assertSame('cancelled', $late->status);
        $this->assertSame('pending_refund', $late->refund_status);
        $this->assertNotNull($late->transaction);
        $this->assertSame('active', $other->fresh()->status);
    }

    public function test_late_payment_for_still_free_slot_is_activated(): void
    {
        Queue::fake();
        $late = $this->existing('20:00', 1, 'pending_payment', now()->subMinutes(10));
        $this->artisan('app:expire-stale-bookings');
        $this->assertSame('cancelled', $late->fresh()->status);

        $this->sendWebhook($late)->assertOk();

        $this->assertSame('active', $late->fresh()->status);
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $booking = $this->existing('20:00', 1, 'pending_payment');

        $this->postJson('/doku/webhook', ['order' => ['invoice_number' => $booking->payment_order_id]])
            ->assertStatus(401);

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    public function test_availability_endpoint_reports_busy_slots_and_reasons(): void
    {
        $this->existing('20:00', 1, 'active');

        $this->postJson(route('booking.check', $this->table), [
            'booking_date' => '2026-10-14', 'duration_hours' => 1,
        ])->assertJsonPath('busy.0.start', '20:00')->assertJsonPath('busy.0.end', '21:00');

        $this->postJson(route('booking.check', $this->table), [
            'booking_date' => '2026-10-14', 'start_time' => '19:30', 'duration_hours' => 1,
        ])->assertJsonPath('available', false)->assertJsonPath('reason', 'conflict');

        $this->postJson(route('booking.check', $this->table), [
            'booking_date' => '2026-10-14', 'start_time' => '21:00', 'duration_hours' => 1,
        ])->assertJsonPath('available', true)->assertJsonPath('end_time', '22:00');
    }

    public function test_deactivation_is_skipped_when_the_next_booking_is_already_running(): void
    {
        $device = $this->partialMock(TableDeviceService::class);
        $device->shouldNotReceive('turnOff');

        $previous = $this->existing('18:00', 1, 'active');
        $this->existing('19:00', 1, 'active');

        (new DeactivateTableDevice($previous))->handle($device);
    }

    public function test_cancel_route_no_longer_exists_and_pages_render(): void
    {
        $booking = $this->existing('20:00', 1, 'pending_payment');

        $this->post('/booking/' . $booking->booking_code . '/cancel')->assertNotFound();
        $this->get(route('booking.create', $this->table))->assertOk()->assertSee('Main Sekarang');
        $this->get(route('booking.confirm', $booking->booking_code))->assertOk()->assertSee('Selesaikan Pembayaran');
        $this->getJson(route('booking.status', $booking->booking_code))->assertJsonPath('status', 'pending_payment');
        $this->get(route('schedule.index'))->assertOk();
    }
}
