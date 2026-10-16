<?php

use App\Models\Booking;
use App\Models\LoyaltyLedger;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Loyalty points.
 *
 * Points are money, so the properties that matter are the ones about money:
 * a balance that always equals its own history, points that cannot be spent
 * twice, and a balance that can never go below zero however the sequence of
 * events is arranged.
 */

beforeEach(function () {
    Setting::flushCache();
    Setting::setMany([
        'loyalty_enabled'            => true,
        'loyalty_pesos_per_point'    => 50,
        'loyalty_point_value'        => 0.50,
        'loyalty_min_redeem'         => 100,
        'loyalty_max_redeem_percent' => 50,
        'loyalty_expiry_months'      => 12,
        'account_discount_enabled'   => false,
    ]);
});

function loyalty(): LoyaltyService
{
    return app(LoyaltyService::class);
}

function loyaltyCustomer(): User
{
    return User::factory()->create(['role' => 'customer', 'is_guest' => false]);
}

function loyaltyBooking(User $user, float $price, string $status = 'confirmed', string $plate = 'LYL 1111'): Booking
{
    $unique  = substr(str_replace('.', '', uniqid('', true)), -6);
    $service = Service::create(['name' => 'Svc '.$unique, 'price' => $price, 'duration' => 30]);
    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => $plate, 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $booking = Booking::create([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00', 'duration' => 30,
        'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);
    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 30]);

    return $booking;
}

/** The invariant: the balance is the sum of its own history. */
function assertLedgerConsistent(User $user): void
{
    $rows = LoyaltyLedger::where('user_id', $user->id)->orderBy('id')->get();
    $running = 0;

    foreach ($rows as $row) {
        $running += $row->points;
        expect($row->balance_after)->toBe($running);
    }

    expect((int) LoyaltyLedger::where('user_id', $user->id)->sum('points'))->toBe($running);
    expect($running)->toBeGreaterThanOrEqual(0);
}

// ── Earning ──────────────────────────────────────────────────────────────

it('earns only when the work is completed', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1000);

    expect(loyalty()->balanceFor($user))->toBe(0);

    $booking->update(['status' => 'completed']);
    loyalty()->earnFor($booking->fresh());

    // 1000 / 50 = 20 points.
    expect(loyalty()->balanceFor($user))->toBe(20);
    assertLedgerConsistent($user);
});

it('does not mint points when a completed booking is saved again', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1000, 'completed');

    loyalty()->earnFor($booking);
    loyalty()->earnFor($booking->fresh());
    loyalty()->earnFor($booking->fresh());

    expect(loyalty()->balanceFor($user))->toBe(20);
});

it('earns on what was actually paid, not the list price', function () {
    // Otherwise a discount would be rewarded twice: once as money off, and
    // again as points on money never spent.
    Setting::setMany(['account_discount_enabled' => true, 'account_discount_percent' => 50]);
    $user    = loyaltyCustomer();
    $service = Service::create(['name' => 'Big', 'price' => 2000, 'duration' => 30]);
    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'T', 'model' => 'V', 'year' => 2020,
        'plate_number' => 'LYL 9999', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->total_price)->toBe(1000.0);

    $booking->update(['status' => 'completed']);
    loyalty()->earnFor($booking->fresh());

    // 1000 paid / 50 = 20, not 2000 / 50 = 40.
    expect(loyalty()->balanceFor($user))->toBe(20);
});

it('earns nothing while loyalty is switched off', function () {
    Setting::setMany(['loyalty_enabled' => false]);
    $user = loyaltyCustomer();

    loyalty()->earnFor(loyaltyBooking($user, 1000, 'completed'));

    expect(loyalty()->balanceFor($user))->toBe(0);
});

// ── Redeeming ────────────────────────────────────────────────────────────

it('redeems points against a booking', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 400, 'test seed');

    $service = Service::create(['name' => 'Svc', 'price' => 1000, 'duration' => 30]);
    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'T', 'model' => 'V', 'year' => 2020,
        'plate_number' => 'LYL 2222', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'points' => 400,
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->points_spent)->toBe(400);
    expect((float) $booking->points_discount)->toBe(200.0);   // 400 × ₱0.50
    expect($booking->total_price)->toBe(800.0);
    expect(loyalty()->balanceFor($user))->toBe(0);
    assertLedgerConsistent($user);
});

it('never lets points cover more than the allowed share of a booking', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 100000, 'test seed');

    $booking = loyaltyBooking($user, 1000);
    // Half of 1000 is 500, which at ₱0.50 a point is 1000 points.
    expect(loyalty()->redeemableAgainst($user, 1000))->toBe(1000);
});

it('refuses to redeem below the minimum', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 50, 'test seed');   // below the 100 minimum

    expect(loyalty()->redeemableAgainst($user, 1000))->toBe(0);
});

it('caps a request at what the customer actually has', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 200, 'test seed');

    $service = Service::create(['name' => 'Svc', 'price' => 1000, 'duration' => 30]);
    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'T', 'model' => 'V', 'year' => 2020,
        'plate_number' => 'LYL 3333', 'color' => 'Red', 'vehicle_type' => 'car',
    ]);

    // Asking for 10,000 with 200 to their name.
    $this->actingAs($user)->postJson('/customer/bookings', [
        'service_ids' => [$service->id], 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'points' => 10000,
    ])->assertOk();

    expect(Booking::sole()->points_spent)->toBe(200);
    expect(loyalty()->balanceFor($user))->toBe(0);
    assertLedgerConsistent($user);
});

it('cannot be driven negative by spending twice', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 300, 'test seed');

    $first  = loyaltyBooking($user, 2000, 'confirmed', 'LYL 4444');
    $second = loyaltyBooking($user, 2000, 'confirmed', 'LYL 5555');

    expect(loyalty()->redeemFor($first, $user, 300))->toBe(150.0);
    // The second spend finds nothing left and takes nothing.
    expect(loyalty()->redeemFor($second, $user->fresh(), 300))->toBe(0.0);

    expect(loyalty()->balanceFor($user))->toBe(0);
    assertLedgerConsistent($user);
});

// ── Reversal ─────────────────────────────────────────────────────────────

it('takes back points when a completed booking is cancelled', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1000, 'completed');
    loyalty()->earnFor($booking);

    expect(loyalty()->balanceFor($user))->toBe(20);

    loyalty()->reverseFor($booking->fresh());

    expect(loyalty()->balanceFor($user))->toBe(0);
    expect($booking->fresh()->points_earned)->toBe(0);
    assertLedgerConsistent($user);
});

it('does not claw a customer into debt for points already spent', function () {
    $user    = loyaltyCustomer();
    $earner  = loyaltyBooking($user, 1000, 'completed', 'LYL 6666');
    loyalty()->earnFor($earner);                                  // +20

    $spender = loyaltyBooking($user, 2000, 'confirmed', 'LYL 7777');
    loyalty()->redeemFor($spender, $user->fresh(), 20);           // -20, balance 0

    // The shop then cancels the completed job. There is nothing to take.
    loyalty()->reverseFor($earner->fresh());

    expect(loyalty()->balanceFor($user))->toBe(0);
    assertLedgerConsistent($user);
});

it('gives back points spent on a booking that gets cancelled', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 400, 'test seed');

    $booking = loyaltyBooking($user, 2000, 'confirmed', 'LYL 8888');
    loyalty()->redeemFor($booking, $user->fresh(), 400);
    expect(loyalty()->balanceFor($user))->toBe(0);

    loyalty()->refundSpentFor($booking->fresh());

    expect(loyalty()->balanceFor($user))->toBe(400);
    expect($booking->fresh()->points_spent)->toBe(0);
    assertLedgerConsistent($user);
});

it('does not refund the same cancelled booking twice', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 400, 'test seed');
    $booking = loyaltyBooking($user, 2000, 'confirmed', 'LYL 1212');
    loyalty()->redeemFor($booking, $user->fresh(), 400);

    loyalty()->refundSpentFor($booking->fresh());
    loyalty()->refundSpentFor($booking->fresh());

    expect(loyalty()->balanceFor($user))->toBe(400);
});

// ── Expiry ───────────────────────────────────────────────────────────────

it('expires points past their date, oldest first', function () {
    $user = loyaltyCustomer();

    $old = loyalty()->adjust($user, 100, 'old points');
    LoyaltyLedger::where('user_id', $user->id)->update(['expires_at' => now()->subDay()->toDateString()]);
    loyalty()->adjust($user, 50, 'fresh points');

    expect(loyalty()->balanceFor($user))->toBe(150);

    $expired = loyalty()->expire();

    expect($expired)->toBe(100);
    expect(loyalty()->balanceFor($user))->toBe(50);
    assertLedgerConsistent($user);
});

it('spends the oldest points first so they expire in order', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 100, 'first');
    loyalty()->adjust($user, 100, 'second');

    $booking = loyaltyBooking($user, 2000, 'confirmed', 'LYL 1313');
    loyalty()->redeemFor($booking, $user->fresh(), 100);

    $lots = LoyaltyLedger::where('user_id', $user->id)
        ->where('reason', LoyaltyLedger::ADJUSTED)->orderBy('id')->get();

    expect($lots[0]->remaining)->toBe(0);     // oldest consumed
    expect($lots[1]->remaining)->toBe(100);   // newest untouched
});

it('records an expiry as its own line rather than shrinking a balance', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 100, 'old points');
    LoyaltyLedger::where('user_id', $user->id)->update(['expires_at' => now()->subDay()->toDateString()]);

    loyalty()->expire();

    $row = LoyaltyLedger::where('reason', LoyaltyLedger::EXPIRED)->sole();
    expect($row->points)->toBe(-100);
    expect($row->describe())->toContain('Expired');
});

it('leaves points alone when expiry is switched off', function () {
    Setting::setMany(['loyalty_expiry_months' => 0]);
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 100, 'never expires');

    expect(LoyaltyLedger::where('user_id', $user->id)->sole()->expires_at)->toBeNull();
    expect(loyalty()->expire())->toBe(0);
});

// ── Admin adjustment ─────────────────────────────────────────────────────

it('records the reason on a manual adjustment', function () {
    $user = loyaltyCustomer();

    loyalty()->adjust($user, 250, 'Goodwill after a delayed service');

    $row = LoyaltyLedger::where('reason', LoyaltyLedger::ADJUSTED)->sole();
    expect($row->points)->toBe(250);
    expect($row->note)->toBe('Goodwill after a delayed service');
});

it('cannot adjust a balance below zero', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 100, 'seed');

    // Asking to remove 500 when only 100 exists removes 100.
    expect(loyalty()->adjust($user, -500, 'correction'))->toBe(-100);
    expect(loyalty()->balanceFor($user))->toBe(0);
    assertLedgerConsistent($user);
});

// ── The booking lifecycle, end to end ────────────────────────────────────

it('earns on completion through the admin booking form', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1500, 'confirmed', 'LYL 1414');
    $admin   = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => $booking->services()->pluck('services.id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => 'completed',
    ]);

    expect(loyalty()->balanceFor($user))->toBe(30);   // 1500 / 50
    assertLedgerConsistent($user);
});

// ── Admin surfaces ───────────────────────────────────────────────────────

it('lets the admin adjust points with a reason, and logs it', function () {
    $user     = loyaltyCustomer();
    $customer = App\Models\Customer::create(['user_id' => $user->id, 'phone' => '09171234567']);
    $admin    = User::factory()->create(['role' => 'admin', 'name' => 'The Admin']);

    $this->actingAs($admin)->post("/admin/customers/{$customer->id}/points", [
        'points' => 250, 'reason' => 'Goodwill after a delayed service',
    ])->assertRedirect();

    expect(loyalty()->balanceFor($user))->toBe(250);

    $log = App\Models\AuditLog::where('action', 'loyalty.adjusted')->sole();
    expect($log->reason)->toBe('Goodwill after a delayed service');
    expect($log->actor_name)->toBe('The Admin');
    assertLedgerConsistent($user);
});

it('refuses an adjustment with no reason', function () {
    $user     = loyaltyCustomer();
    $customer = App\Models\Customer::create(['user_id' => $user->id, 'phone' => '09171234567']);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->post("/admin/customers/{$customer->id}/points", ['points' => 250])
        ->assertSessionHasErrors('reason');

    expect(loyalty()->balanceFor($user))->toBe(0);
});

it('refuses an adjustment of zero', function () {
    $user     = loyaltyCustomer();
    $customer = App\Models\Customer::create(['user_id' => $user->id, 'phone' => '09171234567']);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->post("/admin/customers/{$customer->id}/points", ['points' => 0, 'reason' => 'nothing at all'])
        ->assertSessionHasErrors('points');
});

it('keeps point adjustment away from customers', function () {
    $user     = loyaltyCustomer();
    $customer = App\Models\Customer::create(['user_id' => $user->id, 'phone' => '09171234567']);

    $this->actingAs($user)->post("/admin/customers/{$customer->id}/points", [
        'points' => 99999, 'reason' => 'giving myself points',
    ])->assertStatus(403);

    expect(loyalty()->balanceFor($user))->toBe(0);
});

it('lets the admin change the loyalty rates', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put('/admin/settings/loyalty', [
            'loyalty_enabled' => '1', 'loyalty_pesos_per_point' => 100,
            'loyalty_point_value' => 1.00, 'loyalty_min_redeem' => 50,
            'loyalty_max_redeem_percent' => 25, 'loyalty_expiry_months' => 6,
        ])->assertRedirect();

    Setting::flushCache();
    expect(loyalty()->pesosPerPoint())->toBe(100);
    expect(loyalty()->pointValue())->toBe(1.0);
    expect(loyalty()->maxRedeemPercent())->toBe(25);
});

it('refuses a zero cost per point, which would mint points from nothing', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put('/admin/settings/loyalty', [
            'loyalty_enabled' => '1', 'loyalty_pesos_per_point' => 0,
            'loyalty_point_value' => 1.00, 'loyalty_min_redeem' => 50,
            'loyalty_max_redeem_percent' => 25, 'loyalty_expiry_months' => 6,
        ])->assertSessionHasErrors('loyalty_pesos_per_point');
});

// ── Customer surfaces ────────────────────────────────────────────────────

it('shows the balance and its history to the customer', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 250, 'Goodwill after a delayed service');

    $this->actingAs($user)->get('/customer/dashboard')->assertOk()
        ->assertSee('My Points')
        ->assertSee('250')
        ->assertSee('Goodwill after a delayed service');
});

it('hides the points panel entirely while loyalty is off', function () {
    Setting::setMany(['loyalty_enabled' => false]);

    $this->actingAs(loyaltyCustomer())->get('/customer/dashboard')->assertOk()
        ->assertDontSee('My Points');
});

// ── Always on, with a kill switch ────────────────────────────────────────

it('is on for every account holder without any opt-in', function () {
    // No setting saved at all: a fresh install has the programme running.
    Setting::query()->where('key', 'loyalty_enabled')->delete();
    Setting::flushCache();

    expect(loyalty()->enabled())->toBeTrue();
});

it('still has a switch to stop it in an emergency', function () {
    Setting::setMany(['loyalty_enabled' => false]);
    Setting::flushCache();

    $user = loyaltyCustomer();
    loyalty()->earnFor(loyaltyBooking($user, 1000, 'completed', 'KIL 1111'));

    expect(loyalty()->balanceFor($user))->toBe(0);
});

// ── Idempotency enforced by the database ─────────────────────────────────

it('refuses a second earn row for the same booking at the database', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1000, 'completed', 'IDM 1111');
    loyalty()->earnFor($booking);

    // Bypassing the service entirely — the constraint is the last line of
    // defence against a future code path that forgets to check.
    expect(fn () => LoyaltyLedger::create([
        'user_id' => $user->id, 'booking_id' => $booking->id,
        'points' => 999, 'remaining' => 999, 'balance_after' => 9999,
        'reason' => LoyaltyLedger::EARNED,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);

    expect(loyalty()->balanceFor($user))->toBe(20);
});

it('still allows each different kind of entry on one booking', function () {
    $user    = loyaltyCustomer();
    loyalty()->adjust($user, 500, 'seed');
    $booking = loyaltyBooking($user, 2000, 'confirmed', 'IDM 2222');

    // redeemed, then refunded, then the booking earns later — three
    // different reasons against one booking, all legitimate.
    loyalty()->redeemFor($booking, $user->fresh(), 100);
    loyalty()->refundSpentFor($booking->fresh());

    expect(LoyaltyLedger::where('booking_id', $booking->id)->count())->toBe(2);
    assertLedgerConsistent($user);
});

// ── Verification command ─────────────────────────────────────────────────

it('verifies a healthy ledger', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 300, 'seed');

    $this->artisan('loyalty:verify')->assertSuccessful();
});

it('catches a balance that disagrees with its history', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 300, 'seed');

    // Something wrote to the table without going through the service.
    LoyaltyLedger::where('user_id', $user->id)->update(['balance_after' => 99999]);

    $this->artisan('loyalty:verify')->assertFailed();
});

it('catches points that are in the balance but in no lot', function () {
    $user = loyaltyCustomer();
    loyalty()->adjust($user, 300, 'seed');

    LoyaltyLedger::where('user_id', $user->id)->update(['remaining' => 0]);

    $this->artisan('loyalty:verify')->assertFailed();
});

// ── Reports ──────────────────────────────────────────────────────────────

it('reports what discounts cost and what loyalty owes', function () {
    $user    = loyaltyCustomer();
    $booking = loyaltyBooking($user, 1000, 'completed', 'RPT 1111');
    // Dated inside the report window: the page filters on booking_date and
    // the helper books into the future.
    $booking->update(['booking_date' => today()->toDateString()]);
    loyalty()->earnFor($booking->fresh());

    $d = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/reports')->assertOk()->viewData('discounts');

    expect($d['points_issued'])->toBe(20);
    expect($d['points_outstanding'])->toBe(20);
    // 20 unspent points at ₱0.50 is ₱10 of future discount owed.
    expect($d['liability'])->toBe(10.0);
});

it('shows the liability on the reports page', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/reports')->assertOk()
        ->assertSee('Loyalty Liability')
        ->assertSee('Points Outstanding');
});
