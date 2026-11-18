<?php

use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * My Bookings names the mechanic on the job.
 *
 * It said TBA for every booking, forever, and from two separate causes.
 *
 * The dashboard's own panel read `$b->staff->name ?? 'TBA'`. There is no
 * `staff` relation on Booking — there was one when a mechanic was a user
 * account with role 'staff', and the rename to the mechanics table left this
 * behind. Eloquent answers an unknown relation with null rather than
 * throwing, so `?? 'TBA'` swallowed it and the panel looked like it was
 * working.
 *
 * The booking cards were worse. The server sends the name as `mechanic`,
 * while the card read `b.staff` — undefined — which esc() renders as an empty
 * string, so the person icon printed nothing at all. The only "TBA" a
 * customer could see on a card was `b.amount`, a literal the server set to
 * 'TBA' for every booking and was never going to fill in. That slot shows the
 * mechanic now.
 *
 * The same two keys were in the poll's merge condition, so a mechanic
 * assigned while the customer had the page open never appeared either.
 */
function mechBooking(User $customer, ?Mechanic $mechanic = null, string $status = 'booking_confirmed'): Booking
{
    static $n = 600;
    $n++;

    $service = Service::create([
        'name' => 'Mech Svc '.$n, 'price' => 1500, 'duration' => 30, 'is_active' => true,
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2020,
        'plate_number' => 'MCH '.$n, 'vehicle_type' => 'car',
    ]);

    $booking = Booking::create([
        'user_id'          => $customer->id,
        'vehicle_id'       => $vehicle->id,
        'service_id'       => $service->id,
        'mechanic_id'      => $mechanic?->id,
        'booking_date'     => nextWeekday(),
        'booking_time'     => '10:00',
        'duration'         => 30,
        'status'           => $status,
        'reference_number' => 'MCH-'.$n,
    ]);

    $booking->services()->attach($service->id, ['price' => 1500, 'duration' => 30]);

    return $booking;
}

function mechanicNamed(string $name): Mechanic
{
    return Mechanic::create(['name' => $name, 'status' => 'active', 'contact_number' => '09171234567']);
}

// ── the name, where TBA used to be ───────────────────────────────────────

it('shows the assigned mechanic on the dashboard booking panel', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, mechanicNamed('Mang Kardo Reyes'));

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertSee('Mang Kardo Reyes');
});

it('sends the assigned mechanic to the booking cards', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, mechanicNamed('Bert Santiago'));

    // The cards are rendered in the browser from this array, so what the page
    // has to be given is the name under the key the card reads.
    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toContain('Bert Santiago');
    expect($html)->toContain('"mechanic":"Bert Santiago"');
});

it('keeps TBA when nobody is assigned yet', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, null);

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertSee('TBA');
});

it('falls back to TBA when the mechanic record has been deleted', function () {
    // A booking keeps its mechanic_id, and the row it points at can go. The
    // relation is then null, which must read the same as never having been
    // assigned rather than failing on a property of null.
    $customer = User::factory()->create(['role' => 'customer']);
    $mechanic = mechanicNamed('Departed Mechanic');

    mechBooking($customer, $mechanic);
    $mechanic->delete();

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertSee('TBA')
        ->assertDontSee('Departed Mechanic');
});

// ── the poll, so an assignment appears without a reload ──────────────────

it('sends the mechanic name on the dashboard poll', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, mechanicNamed('Jun Villamor'));

    $this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk()
        ->assertJsonPath('bookings.0.mechanic', 'Jun Villamor');
});

it('sends TBA on the poll when nobody is assigned', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, null);

    $this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk()
        ->assertJsonPath('bookings.0.mechanic', 'TBA');
});

// ── one query, not one per booking ───────────────────────────────────────

it('loads every mechanic in one query, however many bookings there are', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    foreach (range(1, 6) as $i) {
        mechBooking($customer, mechanicNamed('Mechanic '.$i));
    }

    DB::enableQueryLog();

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk();

    $mechanicQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains($q['query'], 'from "mechanics"'))
        ->count();

    DB::disableQueryLog();

    // Six bookings, six mechanics, one query — which is what ->with()
    // buys and what reading through the relation per row would have cost.
    expect($mechanicQueries)->toBe(1);
});

// ── the keys that were wrong are gone ────────────────────────────────────

it('leaves no reference to the staff relation that no longer exists', function () {
    $view = file_get_contents(resource_path('views/dashboard/customer-dashboard.blade.php'));

    // Comments explaining the fault are allowed to say the word; code is not.
    expect($view)->not->toMatch('~\$b->staff\b(?!,)~')
        ->and($view)->not->toContain('b.staff.')
        ->and($view)->not->toContain('fresh.staff')
        ->and($view)->not->toContain('${esc(b.staff)}');
});

it('does not print a price where the mechanic now goes', function () {
    // 'amount' was the literal string 'TBA' in every payload the server sent.
    // Nothing fills it in, and the card slot it occupied belongs to the
    // mechanic now.
    $customer = User::factory()->create(['role' => 'customer']);
    mechBooking($customer, mechanicNamed('Slot Owner'));

    $html = $this->actingAs($customer)->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('"amount":"TBA"');
    expect($html)->toContain('${esc(b.mechanic || \'TBA\')}');
});
