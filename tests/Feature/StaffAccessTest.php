<?php

namespace Tests\Feature;

use App\Jobs\DeactivateTableDevice;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Models\User;
use App\Services\TableDeviceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private MahjongTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        config(['doku.client_id' => 'BRN-TEST', 'doku.secret_key' => 'SK-TEST', 'doku.payment_due_minutes' => 5]);
        Http::fake(['*/checkout/v1/payment' => Http::response(['response' => ['payment' => ['url' => 'https://checkout.test/pay']]])]);

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));

        $this->table = MahjongTable::create(['name' => 'Meja 1', 'capacity' => 4, 'status' => 'available', 'esp32_meja_id' => 1]);
        Pricing::create(['mahjong_table_id' => $this->table->id, 'price_per_hour' => 50000, 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function staff(array $permissions = [], bool $active = true): User
    {
        return User::factory()->create(['role' => 'staff', 'permissions' => $permissions, 'is_active' => $active]);
    }

    private function device(?bool $on = true, ?bool $off = null): void
    {
        $this->partialMock(TableDeviceService::class, function ($mock) use ($on, $off) {
            $on === null ? $mock->shouldNotReceive('turnOn') : $mock->shouldReceive('turnOn')->once()->andReturn($on);
            $off === null ? $mock->shouldNotReceive('turnOff') : $mock->shouldReceive('turnOff')->once()->andReturn($off);
        });
    }

    private function bookCustomer(string $start, int $hours = 1)
    {
        return $this->post(route('booking.store'), [
            'customer_name'  => 'Tamu',
            'customer_phone' => '081234567890',
            'booking_date'   => '2026-10-20',
            'lines'          => [['table_id' => $this->table->id, 'start_time' => $start, 'duration_hours' => $hours]],
        ]);
    }

    private function runningBooking(string $start, int $hours, string $status = 'active'): Booking
    {
        return Booking::create([
            'source'           => 'owner',
            'mahjong_table_id' => $this->table->id,
            'customer_name'    => 'Owner',
            'customer_phone'   => '',
            'booking_date'     => '2026-10-20',
            'start_time'       => $start,
            'duration_hours'   => $hours,
            'total_price'      => 0,
            'status'           => $status,
            'booking_code'     => 'T-' . uniqid(),
        ]);
    }

    public function test_new_users_default_to_staff_without_any_permission(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-pass-1']);

        $this->assertSame('staff', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertFalse($user->fresh()->hasAnyPermission(...array_keys(User::PERMISSIONS)));
    }

    public function test_staff_without_permissions_is_blocked_everywhere_and_lands_on_the_no_access_page(): void
    {
        $user = $this->staff();

        foreach (['dashboard.index', 'dashboard.revenue', 'dashboard.occupancy', 'dashboard.pricing', 'dashboard.bookings',
                  'dashboard.bookings.create', 'dashboard.tables.index', 'dashboard.tables.create', 'dashboard.jeda',
                  'dashboard.accounts.index', 'dashboard.activity'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->actingAs($user)->get('/home')->assertRedirect(route('dashboard.noaccess'));
        $this->actingAs($user)->get(route('dashboard.noaccess'))->assertOk()->assertSee('belum diberi akses');
    }

    public function test_each_permission_unlocks_only_its_own_pages(): void
    {
        $exclusive = [
            'dashboard'       => ['dashboard.index'],
            'revenue'         => ['dashboard.revenue', 'dashboard.occupancy'],
            'pricing'         => ['dashboard.pricing'],
            'bookings_create' => ['dashboard.bookings.create'],
            'tables_manage'   => ['dashboard.tables.create'],
            'pause'           => ['dashboard.jeda'],
        ];

        foreach ($exclusive as $permission => $routes) {
            $user = $this->staff([$permission]);

            foreach ($exclusive as $other => $otherRoutes) {
                foreach ($otherRoutes as $route) {
                    $response = $this->actingAs($user)->get(route($route));
                    $other === $permission ? $response->assertOk() : $response->assertForbidden();
                }
            }
        }

        foreach (['bookings_view', 'bookings_manage', 'bookings_create'] as $permission) {
            $this->actingAs($this->staff([$permission]))->get(route('dashboard.bookings'))->assertOk();
        }
        foreach (['tables_power', 'tables_manage'] as $permission) {
            $this->actingAs($this->staff([$permission]))->get(route('dashboard.tables.index'))->assertOk();
        }
        $this->actingAs($this->staff(['pause', 'pricing']))->get(route('dashboard.bookings'))->assertForbidden();
        $this->actingAs($this->staff(['pause', 'pricing']))->get(route('dashboard.tables.index'))->assertForbidden();
    }

    public function test_actions_need_their_own_permission(): void
    {
        $booking = $this->runningBooking('20:00', 1);

        $viewer = $this->staff(['bookings_view', 'tables_manage', 'pause']);
        $this->actingAs($viewer)->put(route('dashboard.bookings.status', $booking), ['status' => 'cancelled'])->assertForbidden();
        $this->actingAs($viewer)->post(route('dashboard.tables.device', $this->table), ['action' => 'on'])->assertForbidden();
        $this->actingAs($viewer)->put(route('dashboard.pricing.update', $this->table), ['price_per_hour' => 1])->assertForbidden();

        $this->assertSame('active', $booking->fresh()->status);

        $this->device(true);
        $this->actingAs($this->staff(['tables_power']))->post(route('dashboard.tables.device', $this->table), ['action' => 'on'])->assertRedirect();
    }

    public function test_staff_can_never_manage_accounts_even_with_every_permission(): void
    {
        $user = $this->staff(array_keys(User::PERMISSIONS));

        $this->actingAs($user)->get(route('dashboard.accounts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard.activity'))->assertForbidden();
        $this->actingAs($user)->post(route('dashboard.accounts.store'), [
            'name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'password123', 'permissions' => ['pricing'],
        ])->assertForbidden();

        $this->assertSame(0, User::where('email', 'boss@example.com')->count());
    }

    public function test_admin_creates_a_staff_account_with_chosen_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.accounts.store'), [
            'name'        => 'Tablet Bar',
            'email'       => 'bar@example.com',
            'password'    => 'password123',
            'permissions' => ['pause'],
        ])->assertRedirect(route('dashboard.accounts.index'));

        $account = User::where('email', 'bar@example.com')->firstOrFail();
        $this->assertSame('staff', $account->role);
        $this->assertSame(['pause'], $account->permissions);
        $this->assertTrue(Hash::check('password123', $account->password));
        $this->assertTrue($account->is_active);
        $this->assertSame(1, ActivityLog::where('action', 'account_create')->count());

        $this->actingAs($admin)->get(route('dashboard.accounts.index'))->assertOk()->assertSee('Tablet Bar')->assertSee('Jeda meja');
    }

    public function test_account_form_rejects_unknown_permissions_and_duplicate_emails(): void
    {
        $admin = $this->admin();
        $this->staff()->update(['email' => 'dup@example.com']);

        $this->actingAs($admin)->post(route('dashboard.accounts.store'), [
            'name' => 'A', 'email' => 'new@example.com', 'password' => 'password123', 'permissions' => ['make_me_admin'],
        ])->assertSessionHasErrors('permissions.0');

        $this->actingAs($admin)->post(route('dashboard.accounts.store'), [
            'name' => 'A', 'email' => 'dup@example.com', 'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->post(route('dashboard.accounts.store'), [
            'name' => 'A', 'email' => 'short@example.com', 'password' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertSame(0, User::where('email', 'new@example.com')->count());
    }

    public function test_admin_changes_permissions_and_resets_the_password_but_cannot_edit_admin_accounts(): void
    {
        $admin   = $this->admin();
        $account = $this->staff(['pause']);

        $this->actingAs($admin)->put(route('dashboard.accounts.update', $account), [
            'name' => 'Kasir', 'email' => 'kasir@example.com', 'password' => 'brand-new-pass', 'permissions' => ['bookings_view', 'pause'],
        ])->assertRedirect(route('dashboard.accounts.index'));

        $account->refresh();
        $this->assertSame(['bookings_view', 'pause'], $account->permissions);
        $this->assertTrue(Hash::check('brand-new-pass', $account->password));

        $this->actingAs($admin)->put(route('dashboard.accounts.update', $account), [
            'name' => 'Kasir', 'email' => 'kasir@example.com', 'permissions' => [],
        ])->assertRedirect();
        $this->assertTrue(Hash::check('brand-new-pass', $account->fresh()->password));
        $this->assertSame([], $account->fresh()->permissions);

        $this->actingAs($admin)->get(route('dashboard.accounts.edit', $this->admin()))->assertForbidden();
        $this->actingAs($admin)->post(route('dashboard.accounts.toggle', $admin))->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_deactivated_accounts_cannot_log_in_and_are_kicked_out(): void
    {
        $inactive = $this->staff(['pause'], false);
        $inactive->update(['password' => 'password123']);

        $this->post('/login', ['email' => $inactive->email, 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactive)->get(route('dashboard.jeda'))->assertRedirect(route('login'));

        $account = $this->staff(['pause']);
        $this->actingAs($this->admin())->post(route('dashboard.accounts.toggle', $account))->assertRedirect();
        $this->assertFalse($account->fresh()->is_active);
        $this->actingAs($this->admin())->post(route('dashboard.accounts.toggle', $account))->assertRedirect();
        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_login_sends_each_account_to_its_first_allowed_page(): void
    {
        $bar = $this->staff(['pause']);
        $bar->update(['password' => 'password123']);

        $this->post('/login', ['email' => $bar->email, 'password' => 'password123'])->assertRedirect(route('dashboard.jeda'));
        $this->post('/logout');

        $admin = $this->admin();
        $admin->update(['password' => 'password123']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'password123'])->assertRedirect(route('dashboard.index'));
    }

    public function test_staff_pause_turns_the_table_on_and_closes_it_to_customers(): void
    {
        Queue::fake();
        $this->device(true);
        $staff = $this->staff(['pause']);

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5])->assertRedirect();

        $pause = Booking::sole();
        $this->assertSame('staff', $pause->source);
        $this->assertSame('active', $pause->status);
        $this->assertSame(5, $pause->duration_minutes);
        $this->assertSame($staff->id, $pause->created_by);
        $this->assertSame('2026-10-20 12:00:00', $pause->starts_at->toDateTimeString());
        $this->assertSame('2026-10-20 12:05:00', $pause->ends_at->toDateTimeString());
        $this->assertSame(0, (int) $pause->total_price);
        Queue::assertPushed(DeactivateTableDevice::class);

        $log = ActivityLog::sole();
        $this->assertSame('pause_start', $log->action);
        $this->assertSame(5, $log->minutes);
        $this->assertSame($staff->name, $log->user_name);

        $this->bookCustomer('12:00')->assertSessionHasErrors('lines.0.start_time');
        $this->bookCustomer('12:04')->assertSessionHasErrors('lines.0.start_time');
        $this->bookCustomer('12:05')->assertRedirect();
    }

    public function test_staff_can_stop_a_pause_early_and_the_slot_reopens(): void
    {
        Queue::fake();
        $this->device(true, true);
        $staff = $this->staff(['pause']);

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 10]);
        $this->actingAs($staff)->get(route('dashboard.jeda'))->assertOk()->assertSee('Matikan sekarang');
        $this->actingAs($staff)->post(route('dashboard.jeda.stop', $this->table))->assertSessionHas('success');

        $this->assertSame('done', Booking::sole()->status);
        $this->assertSame(['pause_start', 'pause_stop'], ActivityLog::orderBy('id')->pluck('action')->all());

        $this->bookCustomer('12:00')->assertRedirect();
    }

    public function test_pause_is_refused_while_a_booking_is_running_or_about_to_start(): void
    {
        $this->device(null, null);
        $staff = $this->staff(['pause']);

        $this->runningBooking('11:30', 1);
        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 2])
            ->assertSessionHas('error');
        $this->assertSame(1, Booking::count());
    }

    public function test_pause_only_offers_the_minutes_that_fit_before_the_next_booking(): void
    {
        Queue::fake();
        $this->device(true);
        $staff = $this->staff(['pause']);

        $this->runningBooking('12:03', 1);

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5])->assertSessionHas('error');
        $this->assertSame(1, Booking::count());

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 2])->assertSessionHas('success');
        $this->assertSame(2, Booking::count());
    }

    public function test_pause_rejects_other_durations_and_maintenance_tables(): void
    {
        $this->device(null, null);
        $staff = $this->staff(['pause']);

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 7])->assertSessionHasErrors('minutes');

        $this->table->update(['status' => 'maintenance']);
        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5])->assertSessionHas('error');
        $this->assertSame(0, Booking::count());
    }

    public function test_pause_is_cancelled_when_the_table_cannot_be_switched_on(): void
    {
        Queue::fake();
        $this->device(false);
        $staff = $this->staff(['pause']);

        $this->actingAs($staff)->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5])
            ->assertSessionHas('error');

        $this->assertSame('cancelled', Booking::sole()->status);
        $this->assertSame(0, ActivityLog::count());
        $this->bookCustomer('12:00')->assertRedirect();
    }

    public function test_pause_end_job_turns_the_table_off_and_marks_it_done(): void
    {
        Queue::fake();
        $this->device(true, true);
        $this->actingAs($this->staff(['pause']))->post(route('dashboard.jeda.start', $this->table), ['minutes' => 2]);

        $pause = Booking::sole();
        $this->travel(3)->minutes();

        (new DeactivateTableDevice($pause))->handle(app(TableDeviceService::class));

        $this->assertSame('done', $pause->fresh()->status);
    }

    public function test_pauses_stay_out_of_the_booking_list_and_occupancy_by_default(): void
    {
        Queue::fake();
        $this->device(true);
        $this->actingAs($this->staff(['pause']))->post(route('dashboard.jeda.start', $this->table), ['minutes' => 5]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('dashboard.bookings'))->assertOk()->assertDontSee('JDA-');
        $this->actingAs($admin)->get(route('dashboard.bookings', ['source' => 'staff']))->assertOk()->assertSee('JDA-')->assertSee('5 menit');
        $this->actingAs($admin)->get(route('dashboard.index'))->assertOk();
        $this->actingAs($admin)->get(route('schedule.index'))->assertOk()->assertSee('Tidak Tersedia');
    }

    public function test_the_old_admin_pause_route_is_gone(): void
    {
        $this->actingAs($this->admin())->post('/dashboard/tables/' . $this->table->id . '/pause', ['minutes' => 5])->assertNotFound();
    }

    public function test_admin_pages_render_with_the_new_menu(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard.accounts.create'))->assertOk()->assertSee('Jeda meja 2/5/10 menit');
        $this->actingAs($admin)->get(route('dashboard.activity'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.jeda'))->assertOk()->assertSee('Meja 1');
        $this->actingAs($admin)->get(route('dashboard.tables.index'))->assertOk()->assertDontSee('Jeda hingga');

        $this->actingAs($this->staff(['pause']))->get(route('dashboard.jeda'))->assertOk()
            ->assertSee('Jeda Meja')->assertDontSee('Revenue')->assertDontSee('Harga')->assertDontSee('>Akun<', false);
    }
}
