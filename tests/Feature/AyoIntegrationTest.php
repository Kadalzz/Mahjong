<?php

namespace Tests\Feature;

use App\Jobs\ActivateTableDevice;
use App\Models\AyoReservation;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ayo\AyoClient;
use App\Services\Ayo\AyoException;
use App\Services\Ayo\AyoSyncService;
use App\Services\TableDeviceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AyoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;

    private MahjongTable $other;

    private array $ayoBookings = [];

    private array $created = [];

    private array $cancelled = [];

    private string $createMode = 'ok';

    private int $nextId = 50000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        config([
            'doku.client_id' => 'BRN-TEST', 'doku.secret_key' => 'SK-TEST', 'doku.payment_due_minutes' => 5,
            'ayo.enabled' => true, 'ayo.base_url' => 'https://ayo.test/api', 'ayo.venue_code' => 'VENUE12345',
            'ayo.token' => 'TOK', 'ayo.private_key' => 'KEY', 'ayo.webhook_key' => 'hook-secret',
        ]);

        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));

        $this->table = $this->makeTable('Meja 1', 50000, 1, 1623);
        $this->other = $this->makeTable('Meja 2', 50000, 2, 1624);

        Http::fake(fn (Request $request) => $this->route($request));
    }

    private function makeTable(string $name, int $price, int $esp, ?int $field): MahjongTable
    {
        $table = MahjongTable::create(['name' => $name, 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => $esp, 'ayo_field_id' => $field]);
        Pricing::create(['mahjong_table_id' => $table->id, 'price_per_hour' => $price, 'is_active' => true]);

        return $table;
    }

    private function signed(array $data): array
    {
        $sorted = $data;
        ksort($sorted);

        return ['error' => false, 'status_code' => 200, 'data' => $data, 'signature' => hash_hmac('sha512', http_build_query($sorted), 'KEY')];
    }

    private function assertSignedRequest(Request $request): void
    {
        $body = $request->data();
        $this->assertSame('TOK', $body['token']);
        $this->assertSame(app(AyoClient::class)->sign($body), $body['signature']);
    }

    private function route(Request $request)
    {
        $url = $request->url();

        if (str_contains($url, '/checkout/v1/payment')) {
            return Http::response(['response' => ['payment' => ['url' => 'https://checkout.test/pay']]]);
        }

        if (!str_starts_with($url, 'https://ayo.test/api/')) {
            return Http::response([], 404);
        }

        $this->assertSignedRequest($request);
        $body = $request->data();

        if (str_contains($url, 'list-bookings/VENUE12345')) {
            $items = array_values(array_filter($this->ayoBookings, fn ($b) => !isset($body['booking_id']) || $b['booking_id'] === $body['booking_id']));
            return $this->createMode === 'down' ? Http::response(['error' => true, 'message' => 'Server Error'], 500) : Http::response($this->signed($items));
        }

        if (str_contains($url, 'list-fields')) {
            return Http::response($this->signed([['id' => 1623, 'name' => 'Mahjong #1', 'status' => 'ACTIVE'], ['id' => 1624, 'name' => 'Mahjong #2', 'status' => 'ACTIVE']]));
        }

        if (str_contains($url, 'create-reservation')) {
            $this->created[] = $body;

            return match ($this->createMode) {
                'conflict' => Http::response(['error' => true, 'status_code' => 403, 'message' => 'Gagal membuat reservasi, terdapat jadwal yang tidak dapat dibatalkan.', 'signature' => null], 403),
                'down'     => Http::response(['error' => true, 'message' => 'Server Error'], 500),
                'lost'     => $this->storeThenFail($body),
                default    => Http::response($this->signed(['order_detail_id' => ++$this->nextId, 'booking_id' => 'MN/1310/261024/' . $this->nextId, 'status' => 'SUCCESS'])),
            };
        }

        if (str_contains($url, 'cancel-reservation')) {
            $this->cancelled[] = $body['order_detail_id'];
            return Http::response($this->signed(['reservation_time_id' => (string) $body['order_detail_id'], 'status' => 'CANCELLED']));
        }

        return Http::response([], 404);
    }

    private function storeThenFail(array $body)
    {
        $this->ayoBookings[] = [
            'order_detail_id' => ++$this->nextId, 'booking_id' => 'MN/1310/261024/' . $this->nextId, 'field_id' => $body['field_id'],
            'field_name' => 'Mahjong', 'date' => $body['date'], 'start_time' => $body['start_time'],
            'end_time' => $body['end_time'] === '24:00:00' ? '00:00:00' : $body['end_time'], 'total_price' => $body['total_price'],
            'status' => 'SUCCESS', 'booker_name' => $body['user']['name'] ?? '-', 'booker_phone' => '-', 'booker_email' => '-',
            'booking_source' => 'reservation', 'created_at' => '2026-10-24T03:00:00.000000Z',
        ];

        return Http::response(['error' => true, 'message' => 'Gateway Timeout'], 504);
    }

    private function ayoItem(int $id, string $status, string $start, string $end, int $field = 1623, string $source = 'order', string $date = '2026-10-24'): array
    {
        return [
            'order_detail_id' => $id, 'booking_id' => "BK/1310/261024/{$id}", 'field_id' => $field, 'field_name' => 'Mahjong #1',
            'date' => $date, 'start_time' => $start, 'end_time' => $end, 'total_price' => 50000, 'status' => $status,
            'booker_name' => 'Thomas', 'booker_phone' => '6284718278181', 'booker_email' => 'x@example.com',
            'booking_source' => $source, 'created_at' => '2026-10-24T02:00:00.000000Z',
        ];
    }

    private function bookCustomer(string $start, int $hours = 1, ?MahjongTable $table = null, string $date = '2026-10-24')
    {
        return $this->post(route('booking.store'), [
            'customer_name'  => 'Budi Santoso',
            'customer_phone' => '81234567890',
            'booking_date'   => $date,
            'lines'          => [['table_id' => ($table ?? $this->table)->id, 'start_time' => $start, 'duration_hours' => $hours]],
        ]);
    }

    private function sync(): AyoSyncService
    {
        return app(AyoSyncService::class);
    }

    public function test_signature_matches_the_example_in_ayo_documentation(): void
    {
        config(['ayo.private_key' => 'lM8ZgVG73FK6GY49YWIm4pPWSXR8qsuzBHPPRa0N6D6NTir8FbCmiy8ROFZoqrvoIaIcyoT69AqChRlE4UpaoHzhPjFgV4vDKoIZNtfp3PXK4wDUdlbrd0RKFsfKprM89ldEXKL8ruLmPDEpPXKEjEHuFGSGANcRS3Pt8BUx1nZmnJoP8wtkRpfqkxbXFet']);

        $signature = app(AyoClient::class)->sign([
            'token' => '3qUFjksFEG8U5Xi5aXF2lY2WT8jYRDYCFerLmHjWILrawiVpeyjm0OV6tQrl', 'booking_id' => 'BK/0042/230228/0000118',
            'field_name' => 'Lapangan Utama', 'date' => '2023-03-04', 'start_time' => '12:00:00', 'end_time' => '14:00:00', 'total_price' => 2800000,
        ]);

        $this->assertSame('87592e1033c0fc65767ead30b02aca0002f0e43ebb3904b3689dbed8a2033b6a09234ebde2536b0a9de495ea0c48265be768eda2684f9e1f0f211add51c839a3', $signature);
    }

    public function test_responses_with_a_forged_signature_are_rejected(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response(['error' => false, 'data' => [['id' => 1]], 'signature' => 'forged'])]);

        $this->expectException(AyoException::class);
        app(AyoClient::class)->listFields();
    }

    public function test_paid_ayo_booking_closes_the_slot_records_revenue_and_schedules_the_table(): void
    {
        Queue::fake();
        $this->ayoBookings = [$this->ayoItem(16595, 'SUCCESS', '13:00:00', '15:00:00')];

        $this->sync()->syncUpcoming();

        $booking = Booking::where('source', 'ayo')->sole();
        $this->assertSame('active', $booking->status);
        $this->assertSame('SUCCESS', $booking->ayo_status);
        $this->assertSame('AYO-16595', $booking->booking_code);
        $this->assertSame('Thomas', $booking->customer_name);
        $this->assertSame('13:00 – 15:00', $booking->time_range_label);
        $this->assertSame('2 jam', $booking->duration_label);
        $this->assertSame(50000, (int) Transaction::where('payment_method', 'ayo')->sole()->amount);
        Queue::assertPushed(ActivateTableDevice::class);

        $this->bookCustomer('14:00')->assertSessionHasErrors('lines.0.start_time');
        $this->bookCustomer('15:00')->assertRedirect();

        $this->sync()->syncUpcoming();
        $this->assertSame(1, Booking::where('source', 'ayo')->count());
        $this->assertSame(1, Transaction::where('payment_method', 'ayo')->count());
    }

    public function test_ayo_booking_lifecycle_pending_then_paid_then_cancelled(): void
    {
        Queue::fake();
        $this->ayoBookings = [$this->ayoItem(16600, 'PENDING', '13:00:00', '14:00:00')];
        $this->sync()->syncUpcoming();

        $booking = Booking::where('source', 'ayo')->sole();
        $this->assertSame('PENDING', $booking->ayo_status);
        $this->assertSame(0, Transaction::count());
        Queue::assertNothingPushed();
        $this->bookCustomer('13:30')->assertSessionHasErrors('lines.0.start_time');

        $this->ayoBookings = [$this->ayoItem(16600, 'SUCCESS', '13:00:00', '14:00:00')];
        $this->sync()->syncUpcoming();
        $this->assertSame(1, Transaction::count());
        Queue::assertPushed(ActivateTableDevice::class, 1);

        $this->ayoBookings = [$this->ayoItem(16600, 'CANCELLED', '13:00:00', '14:00:00')];
        $this->sync()->syncUpcoming();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(0, Transaction::count());
        $this->bookCustomer('13:30')->assertRedirect();
    }

    public function test_ayo_booking_ending_at_midnight_runs_to_the_next_day(): void
    {
        Queue::fake();
        $this->ayoBookings = [$this->ayoItem(16610, 'SUCCESS', '23:00:00', '00:00:00')];

        $this->sync()->syncUpcoming();

        $this->assertSame('2026-10-25 00:00:00', Booking::where('source', 'ayo')->sole()->ends_at->toDateTimeString());
    }

    public function test_unmapped_fields_and_our_own_reservations_are_ignored(): void
    {
        $this->bookCustomer('16:00')->assertRedirect();
        $own = AyoReservation::sole();

        $this->ayoBookings = [
            $this->ayoItem(99999, 'SUCCESS', '13:00:00', '14:00:00', 7777),
            $this->ayoItem($own->order_detail_id, 'SUCCESS', '16:00:00', '17:00:00', 1623, 'reservation'),
        ];

        $this->sync()->syncUpcoming();

        $this->assertSame(0, Booking::where('source', 'ayo')->count());
    }

    public function test_ayo_booking_overlapping_a_local_booking_is_kept_and_flagged(): void
    {
        Queue::fake();
        Booking::create([
            'source' => 'owner', 'mahjong_table_id' => $this->table->id, 'customer_name' => 'Owner', 'customer_phone' => '',
            'booking_date' => '2026-10-24', 'start_time' => '13:00', 'duration_hours' => 1, 'total_price' => 0,
            'status' => 'active', 'booking_code' => 'T-1',
        ]);
        $this->ayoBookings = [$this->ayoItem(16620, 'SUCCESS', '13:30:00', '14:30:00')];

        $this->sync()->syncUpcoming();

        $this->assertTrue(Booking::where('source', 'ayo')->sole()->ayo_conflict);
        $this->assertSame(1, Booking::ayoIssues()->count());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('dashboard.index'))->assertOk()->assertSee('1 booking perlu dicek');
        $this->actingAs($admin)->get(route('dashboard.bookings', ['ayo_issue' => 1]))->assertOk()->assertSee('Bentrok dengan AYO');
    }

    public function test_customer_booking_closes_the_slot_on_ayo_with_a_signed_request(): void
    {
        $this->bookCustomer('16:15', 2)->assertRedirect();

        $this->assertCount(1, $this->created);
        $sent = $this->created[0];
        $this->assertSame(1623, $sent['field_id']);
        $this->assertSame('2026-10-24', $sent['date']);
        $this->assertSame('16:15:00', $sent['start_time']);
        $this->assertSame('18:15:00', $sent['end_time']);
        $this->assertSame(100000, $sent['total_price']);
        $this->assertSame(['name' => 'Budi Santoso', 'phone' => '081234567890'], $sent['user']);

        $reservation = AyoReservation::sole();
        $this->assertSame('active', $reservation->status);
        $this->assertNotNull($reservation->order_detail_id);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/checkout/v1/payment'));
    }

    public function test_slot_already_taken_on_ayo_rejects_the_order_before_payment(): void
    {
        $this->createMode = 'conflict';

        $this->bookCustomer('16:00')->assertSessionHasErrors('lines.0.start_time');

        $this->assertStringContainsString('dipesan lewat AYO', session('errors')->first('lines.0.start_time'));
        $this->assertSame('cancelled', BookingOrder::sole()->status);
        $this->assertSame('cancelled', Booking::sole()->status);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/checkout/v1/payment'));

        $this->createMode = 'ok';
        $this->bookCustomer('16:00')->assertRedirect();
    }

    public function test_bookings_still_go_through_when_ayo_is_down_and_are_retried_later(): void
    {
        $this->createMode = 'down';
        $this->bookCustomer('16:00')->assertRedirect();

        $reservation = AyoReservation::sole();
        $this->assertSame('retry', $reservation->status);
        $this->assertSame(1, Booking::ayoIssues()->count());

        $this->createMode = 'ok';
        $this->artisan('app:sync-ayo')->assertSuccessful();

        $this->assertSame('active', $reservation->fresh()->status);
        $this->assertSame(0, Booking::ayoIssues()->count());
    }

    public function test_reservation_that_ayo_saved_despite_an_error_is_adopted_instead_of_duplicated(): void
    {
        $this->createMode = 'lost';
        $this->bookCustomer('16:00')->assertRedirect();

        $reservation = AyoReservation::sole();
        $this->assertSame('retry', $reservation->status);
        $this->assertCount(1, $this->created);

        $this->createMode = 'conflict';
        $this->artisan('app:sync-ayo')->assertSuccessful();

        $reservation->refresh();
        $this->assertSame('active', $reservation->status);
        $this->assertSame((int) $this->ayoBookings[0]['order_detail_id'], (int) $reservation->order_detail_id);
        $this->assertCount(1, $this->created);
        $this->assertSame(0, Booking::where('source', 'ayo')->count());
        $this->assertSame(0, Booking::ayoIssues()->count());
    }

    public function test_unpaid_booking_releases_the_ayo_slot_when_its_hold_expires(): void
    {
        $this->bookCustomer('16:00')->assertRedirect();
        $reservation = AyoReservation::sole();

        $this->travel(6)->minutes();
        $this->artisan('app:expire-stale-bookings')->assertSuccessful();

        $this->assertSame([(int) $reservation->order_detail_id], array_map('intval', $this->cancelled));
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_overnight_booking_is_split_into_two_ayo_reservations(): void
    {
        $this->bookCustomer('23:00', 2)->assertRedirect();

        $this->assertCount(2, $this->created);
        $this->assertSame(['2026-10-24', '23:00:00', '24:00:00', 50000], [$this->created[0]['date'], $this->created[0]['start_time'], $this->created[0]['end_time'], $this->created[0]['total_price']]);
        $this->assertSame(['2026-10-25', '00:00:00', '01:00:00', 50000], [$this->created[1]['date'], $this->created[1]['start_time'], $this->created[1]['end_time'], $this->created[1]['total_price']]);
    }

    public function test_admin_bookings_are_pushed_but_staff_pauses_are_not(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.bookings.store'), [
            'source' => 'block', 'booking_date' => '2026-10-24',
            'lines' => [['table_id' => $this->other->id, 'start_time' => '18:00', 'duration_hours' => 1]],
        ])->assertRedirect(route('dashboard.bookings'));
        $this->assertCount(1, $this->created);
        $this->assertSame(1624, $this->created[0]['field_id']);
        $this->assertArrayNotHasKey('user', $this->created[0]);

        $this->partialMock(TableDeviceService::class, fn ($m) => $m->shouldReceive('turnOn')->andReturn(true));
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['pause']]);
        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5])->assertSessionHas('success');
        $this->assertCount(1, $this->created);
    }

    public function test_webhook_needs_the_secret_key_and_trusts_only_data_fetched_from_ayo(): void
    {
        Queue::fake();
        $claim = [$this->ayoItem(16630, 'SUCCESS', '13:00:00', '14:00:00')];

        $this->postJson('/ayo/webhook', $claim)->assertStatus(401);
        $this->postJson('/ayo/webhook?key=wrong', $claim)->assertStatus(401);
        $this->assertSame(0, Booking::count());

        $this->ayoBookings = [$this->ayoItem(16630, 'PENDING', '13:00:00', '14:00:00')];

        $this->postJson('/ayo/webhook?key=hook-secret', $claim)
            ->assertOk()->assertExactJson(['error' => false, 'status_code' => 200, 'message' => 'Success']);

        $booking = Booking::where('source', 'ayo')->sole();
        $this->assertSame('PENDING', $booking->ayo_status);
        Queue::assertNothingPushed();
    }

    public function test_webhook_asks_ayo_to_retry_when_the_booking_cannot_be_confirmed(): void
    {
        $this->createMode = 'down';

        $this->postJson('/ayo/webhook?key=hook-secret', [$this->ayoItem(16640, 'SUCCESS', '13:00:00', '14:00:00')])->assertStatus(500);
        $this->assertSame(0, Booking::count());
    }

    public function test_nothing_is_sent_to_ayo_when_the_integration_is_off(): void
    {
        config(['ayo.enabled' => false]);

        $this->bookCustomer('16:00')->assertRedirect();
        $this->postJson('/ayo/webhook?key=hook-secret', [])->assertStatus(401);
        $this->artisan('app:sync-ayo')->expectsOutputToContain('tidak aktif')->assertSuccessful();

        $this->assertSame([], $this->created);
        $this->assertSame(0, AyoReservation::count());
    }

    public function test_table_edit_page_lists_ayo_fields_and_saves_the_mapping(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table = $this->makeTable('Meja 3', 50000, 3, null);

        $this->actingAs($admin)->get(route('dashboard.tables.edit', $table))->assertOk()->assertSee('Mahjong #2 (ID 1624)');

        $this->actingAs($admin)->put(route('dashboard.tables.update', $table), [
            'name' => 'Meja 3', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 3, 'ayo_field_id' => 1624,
        ])->assertSessionHasErrors('ayo_field_id');

        $this->actingAs($admin)->put(route('dashboard.tables.update', $table), [
            'name' => 'Meja 3', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 3, 'ayo_field_id' => 1625,
        ])->assertRedirect();
        $this->assertSame(1625, (int) $table->fresh()->ayo_field_id);
    }
}
