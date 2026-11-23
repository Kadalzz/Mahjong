<?php

namespace Tests\Feature;

use App\Jobs\ActivateTableDevice;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;
    private MahjongTable $vip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Queue::fake();

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

        $this->travelTo(Carbon::parse('2026-11-24 19:00:00'));

        $this->table = $this->makeTable('Meja 1 - Reguler', 50000, 1);
        $this->vip = $this->makeTable('Meja 4 - VIP', 75000, 4);
    }

    private function makeTable(string $name, int $price, int $espId): MahjongTable
    {
        $table = MahjongTable::create(['name' => $name, 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => $espId]);
        Pricing::create(['mahjong_table_id' => $table->id, 'price_per_hour' => $price, 'is_active' => true]);

        return $table;
    }

    private function voucher(array $attributes = []): Voucher
    {
        return Voucher::create($attributes + [
            'code'           => Voucher::generateCode(),
            'label'          => 'Kolaborasi Tes',
            'discount_type'  => Voucher::TYPE_AMOUNT,
            'discount_value' => 20000,
        ]);
    }

    private function book(?string $code, array $lines = null, string $phone = '081234567890')
    {
        $lines ??= [[$this->table->id, '20:00', 2]];

        return $this->post(route('booking.store'), [
            'customer_name'  => 'Tes',
            'customer_phone' => $phone,
            'booking_date'   => '2026-11-24',
            'voucher_code'   => $code,
            'lines'          => array_map(fn ($l) => ['table_id' => $l[0], 'start_time' => $l[1], 'duration_hours' => $l[2]], $lines),
        ]);
    }

    private function sendWebhook(BookingOrder $order, string $status = 'SUCCESS')
    {
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

    public function test_admin_generates_a_batch_of_distinct_codes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('dashboard.vouchers.store'), [
            'label' => 'Kolaborasi Kopi', 'count' => 25, 'discount_type' => 'amount', 'discount_value' => 25000, 'expires_on' => '2026-12-31',
        ])->assertRedirect(route('dashboard.vouchers.index', ['label' => 'Kolaborasi Kopi', 'status' => 'available']));

        $codes = Voucher::pluck('code');
        $this->assertCount(25, $codes);
        $this->assertCount(25, $codes->unique());
        $codes->each(fn ($code) => $this->assertMatchesRegularExpression('/^HZ-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $code));

        $this->get(route('dashboard.vouchers.index', ['label' => 'Kolaborasi Kopi', 'status' => 'available']))->assertOk()
            ->assertSee('Kode yang masih tersedia (25)')
            ->assertSee($codes->first())
            ->assertSee('Rp 25.000');
        $this->get(route('dashboard.activity'))->assertSee('Voucher dibuat');
    }

    public function test_percent_must_be_at_most_one_hundred_and_staff_cannot_generate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('dashboard.vouchers.store'), [
            'label' => 'X', 'count' => 1, 'discount_type' => 'percent', 'discount_value' => 150,
        ])->assertSessionHasErrors('discount_value');

        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['bookings_view']]);
        $this->actingAs($staff)->get(route('dashboard.vouchers.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('dashboard.vouchers.store'), [
            'label' => 'X', 'count' => 1, 'discount_type' => 'amount', 'discount_value' => 1000,
        ]);
        $this->assertSame(0, Voucher::count());
    }

    public function test_voucher_check_reports_the_discount(): void
    {
        $voucher = $this->voucher(['discount_type' => 'percent', 'discount_value' => 20, 'max_discount' => 15000]);

        $this->postJson(route('booking.voucher'), ['code' => strtolower($voucher->code), 'subtotal' => 100000])
            ->assertJson(['valid' => true, 'code' => $voucher->code, 'discount' => 15000]);
        $this->postJson(route('booking.voucher'), ['code' => 'HZ-NOPE-NOPE', 'subtotal' => 100000])
            ->assertJson(['valid' => false, 'message' => 'Kode voucher tidak ditemukan.']);
    }

    public function test_amount_voucher_cuts_the_qris_amount_and_is_used_once_paid(): void
    {
        $voucher = $this->voucher();

        $this->book($voucher->code)->assertRedirect();
        $order = BookingOrder::sole();

        $this->assertSame(80000, (int) $order->total_price);
        $this->assertSame(20000, (int) $order->discount_amount);
        $this->assertSame($voucher->code, $order->voucher_code);
        Http::assertSent(fn ($request) => $request['order']['amount'] === 80000
            && array_sum(array_column($request['order']['line_items'], 'price')) === 80000);

        $this->assertSame('held', $voucher->fresh()->status());
        $this->get(route('booking.confirm', $order->code))->assertSee('Voucher ' . $voucher->code)->assertSee('−Rp 20.000', false);

        $this->sendWebhook($order)->assertOk();

        $this->assertSame('used', $voucher->fresh()->status());
        $this->assertSame(80000, (int) Transaction::sum('amount'));
    }

    public function test_a_used_voucher_cannot_be_used_again(): void
    {
        $voucher = $this->voucher();
        $this->book($voucher->code);
        $this->sendWebhook(BookingOrder::sole());

        $this->book($voucher->code, [[$this->vip->id, '20:00', 1]])->assertSessionHasErrors(['voucher_code' => 'Kode voucher ini sudah pernah dipakai.']);
        $this->assertSame(1, BookingOrder::count());
    }

    public function test_a_voucher_waiting_for_payment_is_blocked_then_released_when_the_hold_expires(): void
    {
        $voucher = $this->voucher();
        $this->book($voucher->code);

        $this->book($voucher->code, [[$this->vip->id, '20:00', 1]], '089999999999')
            ->assertSessionHasErrors('voucher_code');

        $this->travel(6)->minutes();
        $this->artisan('app:expire-stale-bookings');

        $this->assertSame('available', $voucher->fresh()->status());
        $this->book($voucher->code, [[$this->vip->id, '20:00', 1]], '089999999999')->assertSessionHasNoErrors();
        $this->assertSame(55000, (int) BookingOrder::latest('id')->first()->total_price);
    }

    public function test_an_expired_hold_is_released_even_before_the_cleanup_runs(): void
    {
        $voucher = $this->voucher();
        $this->book($voucher->code);
        $first = BookingOrder::sole();

        $this->travel(6)->minutes();
        $this->book($voucher->code, [[$this->vip->id, '20:00', 1]])->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $first->fresh()->status);
        $this->assertSame(BookingOrder::latest('id')->first()->id, $voucher->fresh()->booking_order_id);
    }

    public function test_failed_payment_releases_the_voucher(): void
    {
        $voucher = $this->voucher();
        $this->book($voucher->code);

        $this->sendWebhook(BookingOrder::sole(), 'FAILED')->assertOk();

        $this->assertSame('available', $voucher->fresh()->status());
    }

    public function test_full_voucher_books_for_free_without_qris_and_turns_the_table_on(): void
    {
        $voucher = $this->voucher(['discount_value' => 500000]);

        $this->book($voucher->code)->assertRedirect(route('booking.confirm', BookingOrder::sole()->code));
        $order = BookingOrder::sole();

        Http::assertNothingSent();
        $this->assertSame(0, (int) $order->total_price);
        $this->assertSame(100000, (int) $order->discount_amount);
        $this->assertSame('active', $order->status);
        $this->assertSame('active', Booking::sole()->status);
        $this->assertSame('used', $voucher->fresh()->status());
        $this->assertSame('VOUCHER', Transaction::sole()->payment_method);
        $this->assertSame(0, (int) Transaction::sole()->amount);
        Queue::assertPushed(ActivateTableDevice::class);

        $this->get(route('booking.confirm', $order->code))->assertSee('Booking Berhasil!');
    }

    public function test_discount_is_split_across_tables_and_never_goes_negative(): void
    {
        $voucher = $this->voucher(['discount_value' => 70001]);

        $this->book($voucher->code, [[$this->table->id, '20:00', 1], [$this->vip->id, '20:00', 1]]);
        $order = BookingOrder::sole();

        $prices = $order->bookings->pluck('total_price')->map(fn ($p) => (int) $p)->all();
        $this->assertSame(54999, array_sum($prices));
        $this->assertSame(54999, (int) $order->total_price);
        foreach ($prices as $price) {
            $this->assertGreaterThanOrEqual(0, $price);
        }
    }

    public function test_expired_or_unknown_codes_are_refused_with_a_clear_message(): void
    {
        $voucher = $this->voucher(['expires_on' => '2026-11-23']);

        $this->book($voucher->code)->assertSessionHasErrors(['voucher_code' => 'Kode voucher ini sudah kedaluwarsa.']);
        $this->book('HZ-AAAA-AAAA')->assertSessionHasErrors(['voucher_code' => 'Kode voucher tidak ditemukan.']);
        $this->assertSame(0, BookingOrder::count());

        $this->book($this->voucher(['expires_on' => '2026-11-24'])->code)->assertSessionHasNoErrors();
    }

    public function test_used_vouchers_cannot_be_deleted_but_unused_ones_can(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $used = $this->voucher();
        $this->book($used->code);
        $this->sendWebhook(BookingOrder::sole());
        $spare = $this->voucher();

        $this->actingAs($admin)->delete(route('dashboard.vouchers.destroy', $used));
        $this->actingAs($admin)->delete(route('dashboard.vouchers.destroy', $spare));

        $this->assertNotNull($used->fresh());
        $this->assertNull($spare->fresh());
        $this->get(route('dashboard.vouchers.index'))->assertSee(BookingOrder::sole()->code)->assertSee('Terpakai');
    }

    public function test_booking_form_has_the_voucher_field(): void
    {
        $this->get(route('booking.create', $this->table))->assertOk()
            ->assertSee('Kode Voucher (opsional)')
            ->assertSee('name="voucher_code"', false)
            ->assertSee('booking\/voucher', false);
    }
}
