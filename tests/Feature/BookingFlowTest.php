<?php

namespace Tests\Feature;

use App\Jobs\ActivateTableDevice;
use App\Jobs\DeactivateTableDevice;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Transaction;
use App\Models\User;
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
        return $this->bookLines([[$this->table->id, $start, $hours]], $date);
    }

    private function bookLines(array $lines, string $date = '2026-10-14')
    {
        return $this->post(route('booking.store'), [
            'customer_name'  => 'Tes',
            'customer_phone' => '081234567890',
            'booking_date'   => $date,
            'lines'          => array_map(fn ($l) => [
                'table_id' => $l[0], 'start_time' => $l[1], 'duration_hours' => $l[2],
            ], $lines),
        ]);
    }

    private function makeTable(string $name, int $price, int $espId): MahjongTable
    {
        $table = MahjongTable::create(['name' => $name, 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => $espId]);
        Pricing::create(['mahjong_table_id' => $table->id, 'price_per_hour' => $price, 'is_active' => true]);

        return $table;
    }

    private function existing(string $start, int $hours, string $status, ?Carbon $createdAt = null, ?MahjongTable $table = null): Booking
    {
        $table ??= $this->table;

        $order = BookingOrder::create([
            'code'             => BookingOrder::generateCode(),
            'customer_name'    => 'Lain',
            'customer_phone'   => '081111111111',
            'booking_date'     => '2026-10-14',
            'total_price'      => 50000 * $hours,
            'status'           => $status,
            'payment_order_id' => 'MJG-T-' . uniqid(),
        ]);

        $booking = Booking::create([
            'booking_order_id' => $order->id,
            'mahjong_table_id' => $table->id,
            'customer_name'    => 'Lain',
            'customer_phone'   => '081111111111',
            'booking_date'     => '2026-10-14',
            'start_time'       => $start,
            'duration_hours'   => $hours,
            'end_time'         => Booking::computeEndTime($start, $hours),
            'total_price'      => 50000 * $hours,
            'status'           => $status,
            'booking_code'     => $order->code . '-1',
        ]);

        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
            $booking->forceFill(['created_at' => $createdAt])->save();
        }

        return $booking->fresh('order');
    }

    private function sendWebhook(Booking|BookingOrder $target, string $status = 'SUCCESS')
    {
        $order = $target instanceof BookingOrder ? $target : $target->order;

        $body = json_encode([
            'order'       => ['invoice_number' => $order->payment_order_id, 'amount' => (int) $order->total_price],
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

        $this->book('19:01')->assertSessionHasErrors('lines.0.start_time');
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
        $this->assertSame('https://checkout.test/pay', $booking->order->payment_url);
    }

    public function test_start_time_in_the_past_is_rejected_but_play_now_grace_is_allowed(): void
    {
        $this->book('18:00')->assertSessionHasErrors('lines.0.start_time');
        $this->book('18:56')->assertRedirect();
    }

    public function test_booking_can_run_past_midnight_and_blocks_the_next_day(): void
    {
        $this->book('23:30', 2)->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame('01:30', substr($booking->end_time, 0, 5));
        $this->assertSame('2026-10-15 01:30:00', $booking->ends_at->toDateTimeString());
        $this->assertTrue($booking->endsNextDay());

        $this->book('00:30', 1, '2026-10-15')->assertSessionHasErrors('lines.0.start_time');
        $this->book('01:30', 1, '2026-10-15')->assertRedirect();
        $this->book('22:45', 1)->assertSessionHasErrors('lines.0.start_time');
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
        $this->book('20:00')->assertSessionHasErrors('lines.0.start_time');

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
        $this->get(route('booking.confirm', $booking->order->code))->assertOk()->assertSee('Selesaikan Pembayaran');
        $this->getJson(route('booking.status', $booking->order->code))->assertJsonPath('status', 'pending_payment');
        $this->get(route('schedule.index'))->assertOk();
    }

    public function test_customer_can_book_several_tables_in_one_order_with_different_times(): void
    {
        $vip = $this->makeTable('Meja 4 - VIP', 75000, 4);
        $vvip = $this->makeTable('Meja 6 - VVIP', 100000, 6);

        $this->bookLines([
            [$this->table->id, '20:00', 2],
            [$vip->id, '20:00', 1],
            [$vvip->id, '21:30', 3],
        ])->assertRedirect();

        $order = BookingOrder::with('bookings')->sole();
        $this->assertSame(3, $order->bookings->count());
        $this->assertSame(50000 * 2 + 75000 + 100000 * 3, (int) $order->total_price);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('https://checkout.test/pay', $order->payment_url);
        $this->assertSame(['20:00', '20:00', '21:30'], $order->bookings->sortBy('mahjong_table_id')->map(fn ($b) => substr($b->start_time, 0, 5))->values()->all());

        Http::assertSent(fn ($request) => str_contains($request->url(), '/checkout/v1/payment')
            && $request['order']['amount'] === 475000
            && count($request['order']['line_items']) === 3);
    }

    public function test_one_conflicting_table_rejects_the_order_and_names_that_table(): void
    {
        $vip = $this->makeTable('Meja 4 - VIP', 75000, 4);
        $this->existing('20:00', 1, 'active', table: $vip);

        $this->bookLines([
            [$this->table->id, '20:00', 1],
            [$vip->id, '20:30', 1],
        ])->assertSessionHasErrors(['lines.1.start_time', 'lines'])
          ->assertSessionDoesntHaveErrors('lines.0.start_time');

        $this->assertSame(1, BookingOrder::count());
        $this->assertStringContainsString('Meja 4 - VIP', session('errors')->first('lines.1.start_time'));
    }

    public function test_more_than_six_tables_or_duplicate_tables_are_rejected(): void
    {
        $tables = collect(range(2, 7))->map(fn ($i) => $this->makeTable("Meja {$i}", 50000, $i));
        $lines = $tables->push($this->table)->map(fn ($t) => [$t->id, '20:00', 1])->all();

        $this->bookLines($lines)->assertSessionHasErrors('lines');
        $this->bookLines([[$this->table->id, '20:00', 1], [$this->table->id, '22:00', 1]])->assertSessionHasErrors('lines.1.table_id');
        $this->assertSame(0, BookingOrder::count());
    }

    public function test_paying_a_multi_table_order_activates_every_table(): void
    {
        Queue::fake();
        $device = $this->partialMock(TableDeviceService::class);
        $device->shouldReceive('turnOn')->once()->andReturn(true);

        $vip = $this->makeTable('Meja 4 - VIP', 75000, 4);
        $this->bookLines([[$this->table->id, '19:00', 1], [$vip->id, '21:00', 1]])->assertRedirect();
        $order = BookingOrder::sole();

        $this->sendWebhook($order)->assertOk();

        $order->refresh();
        $this->assertSame('active', $order->status);
        $this->assertSame(['active', 'active'], $order->bookings->pluck('status')->all());
        $this->assertSame(2, Transaction::count());
        Queue::assertPushed(ActivateTableDevice::class, 1);
        Queue::assertPushed(DeactivateTableDevice::class, 1);
    }

    public function test_late_payment_refunds_only_the_tables_that_were_taken(): void
    {
        Queue::fake();
        $vip = $this->makeTable('Meja 4 - VIP', 75000, 4);
        $this->bookLines([[$this->table->id, '20:00', 1], [$vip->id, '20:00', 1]])->assertRedirect();
        $order = BookingOrder::sole();

        $this->travel(10)->minutes();
        $this->artisan('app:expire-stale-bookings');
        $this->existing('20:00', 1, 'active', table: $vip);

        $this->sendWebhook($order)->assertOk();

        $lines = $order->fresh()->bookings->keyBy('mahjong_table_id');
        $this->assertSame('active', $lines[$this->table->id]->status);
        $this->assertSame('cancelled', $lines[$vip->id]->status);
        $this->assertSame('pending_refund', $lines[$vip->id]->refund_status);
        $this->assertSame('active', $order->fresh()->status);
        $this->get(route('booking.confirm', $order->code))->assertOk()->assertSee('Sebagian Meja Sudah Terisi');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function adminBook(string $source, array $lines, array $extra = [])
    {
        return $this->actingAs($this->admin())->post(route('dashboard.bookings.store'), array_merge([
            'source'       => $source,
            'booking_date' => '2026-10-14',
            'lines'        => array_map(fn ($l) => ['table_id' => $l[0], 'start_time' => $l[1], 'duration_hours' => $l[2]], $lines),
        ], $extra));
    }

    public function test_admin_owner_booking_is_free_blocks_customers_and_turns_tables_on(): void
    {
        Queue::fake();
        $vip = $this->makeTable('Meja 4 - VIP', 75000, 4);

        $this->adminBook('owner', [[$this->table->id, '20:00', 2], [$vip->id, '20:00', 2]])
            ->assertRedirect(route('dashboard.bookings'));

        $order = BookingOrder::with('bookings.transaction')->sole();
        $this->assertSame('owner', $order->source);
        $this->assertSame('Owner', $order->customer_name);
        $this->assertSame('active', $order->status);
        $this->assertSame(0, (int) $order->total_price);
        $this->assertTrue($order->bookings->every(fn ($b) => $b->transaction && (int) $b->transaction->amount === 0));
        Queue::assertPushed(ActivateTableDevice::class, 2);

        $this->book('21:00')->assertSessionHasErrors('lines.0.start_time');
        Http::assertNothingSent();
    }

    public function test_admin_cash_booking_records_revenue_at_table_price(): void
    {
        Queue::fake();

        $this->adminBook('cash', [[$this->table->id, '20:00', 2]], ['customer_name' => 'Budi'])->assertRedirect();

        $order = BookingOrder::sole();
        $this->assertSame('cash', $order->source);
        $this->assertSame(100000, (int) $order->total_price);
        $this->assertSame(100000, (int) Transaction::sum('amount'));
        $this->assertSame('cash', Transaction::first()->payment_method);
    }

    public function test_admin_block_closes_the_slot_without_turning_tables_on_or_charging(): void
    {
        Queue::fake();
        $device = $this->partialMock(TableDeviceService::class);
        $device->shouldNotReceive('turnOn');

        $this->adminBook('block', [[$this->table->id, '18:30', 3]], ['notes' => 'Perbaikan lampu'])->assertRedirect();

        $order = BookingOrder::sole();
        $this->assertSame('block', $order->source);
        $this->assertSame(0, Transaction::count());
        Queue::assertNothingPushed();

        $this->book('20:00')->assertSessionHasErrors('lines.0.start_time');
        $this->get(route('schedule.index'))->assertOk()->assertSee('Tidak Tersedia');
    }

    public function test_admin_booking_also_respects_existing_bookings(): void
    {
        $this->existing('20:00', 1, 'active');

        $this->adminBook('owner', [[$this->table->id, '20:30', 1]])->assertSessionHasErrors('lines.0.start_time');
        $this->assertSame(1, BookingOrder::count());
    }

    public function test_customers_cannot_reach_the_admin_booking_form(): void
    {
        $this->get(route('dashboard.bookings.create'))->assertRedirect(route('login'));
        $this->post(route('dashboard.bookings.store'), ['source' => 'owner'])->assertRedirect(route('login'));
        $this->assertSame(0, BookingOrder::count());
    }

    public function test_admin_pages_render(): void
    {
        $this->existing('20:00', 1, 'active');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard.bookings'))->assertOk()->assertSee('Buat Booking');
        $this->actingAs($admin)->get(route('dashboard.bookings.create'))->assertOk()->assertSee('Owner')->assertSee('Tunai')->assertSee('Blokir');
    }

    public function test_booking_can_last_48_hours_and_blocks_every_day_it_covers(): void
    {
        $this->book('20:00', 48)->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame('2026-10-16 20:00:00', $booking->ends_at->toDateTimeString());
        $this->assertSame('20:00 – 20:00 (+2 hari)', $booking->time_range_label);

        $this->book('12:00', 1, '2026-10-15')->assertSessionHasErrors('lines.0.start_time');
        $this->book('20:00', 1, '2026-10-16')->assertRedirect();
        $this->book('20:00', 49, '2026-10-17')->assertSessionHasErrors('lines.0.duration_hours');
    }

    public function test_order_above_the_qris_limit_is_rejected_for_customers_but_allowed_for_cash(): void
    {
        config(['doku.max_amount' => 10000000]);
        $tables = collect([4, 5, 6])->map(fn ($i) => $this->makeTable("Meja {$i} - VVIP", 100000, $i));

        $this->bookLines($tables->map(fn ($t) => [$t->id, '20:00', 48])->all())
            ->assertSessionHasErrors('lines');
        $this->assertStringContainsString('batas pembayaran QRIS', session('errors')->first('lines'));
        $this->assertSame(0, BookingOrder::count());
        Http::assertNothingSent();

        Queue::fake();
        $this->adminBook('cash', $tables->map(fn ($t) => [$t->id, '20:00', 48])->all())->assertRedirect(route('dashboard.bookings'));
        $this->assertSame(14400000, (int) BookingOrder::sole()->total_price);
    }

    public function test_order_exactly_at_the_qris_limit_is_allowed(): void
    {
        config(['doku.max_amount' => 100000]);

        $this->book('20:00', 2)->assertRedirect();
        $this->assertSame(1, BookingOrder::count());
    }
}
