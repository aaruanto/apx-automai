<?php

/**
 * Bundles where a customer actually books.
 *
 * Admin/BundleTest covers pricing, the admin form and the booking endpoint's
 * refusals. This file covers the other half: what reaches the Book a Service
 * screen, which is where the bundle strip renders from. The strip is drawn in
 * the browser from the payload the controller sends, so a bundle that should
 * not be bookable must be absent from that payload rather than merely hidden
 * by CSS — and every field a card prints has to be in it, or the card renders
 * the word "undefined" at a customer.
 */

use App\Models\Booking;
use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Own helpers rather than Admin/BundleTest's makeBundle(): Pest loads every
// test file into one global scope, so a shared name there is a fatal
// redeclaration here the moment either file grows a second one.
function stripBundle(float $price, array $servicePrices, array $attrs = []): Bundle
{
    $bundle = Bundle::create(array_merge([
        'name'      => 'Strip Bundle '.uniqid(),
        'price'     => $price,
        'is_active' => true,
    ], $attrs));

    foreach ($servicePrices as $p) {
        $bundle->services()->attach(
            Service::create(['name' => 'Strip Svc '.uniqid(), 'price' => $p, 'duration' => 30])->id
        );
    }

    return $bundle->load('services');
}

function stripCustomer(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function stripVehicle(User $owner, string $plate, string $type = 'car'): Vehicle
{
    return Vehicle::create([
        'user_id' => $owner->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => $plate, 'color' => 'Red',
        'vehicle_type' => $type,
    ]);
}

/** The bundles the page was handed, decoded from the JSON the view embeds. */
function bundlePayload($page): array
{
    return collect($page->viewData('bundlesJs'))->map(fn ($b) => (array) $b)->all();
}

// ── What reaches the booking screen ──────────────────────────────────────

it('offers only bookable bundles to the booking screen', function () {
    stripBundle(2000, [1500, 800], ['name' => 'Running Now']);
    stripBundle(2000, [1500, 800], ['name' => 'Switched Off', 'is_active' => false]);
    stripBundle(2000, [1500, 800], ['name' => 'Already Ended', 'ends_at' => now()->subDay()]);
    stripBundle(2000, [1500, 800], ['name' => 'Not Yet Started', 'starts_at' => now()->addWeek()]);

    $page  = $this->actingAs(stripCustomer())->get('/customer/dashboard')->assertOk();
    $names = collect(bundlePayload($page))->pluck('name');

    expect($names)->toContain('Running Now');
    expect($names)->not->toContain('Switched Off');
    expect($names)->not->toContain('Already Ended');
    expect($names)->not->toContain('Not Yet Started');
});

it('carries every field a bundle card prints', function () {
    // A card shows the saving, the struck list price, the bundle price, how
    // many services are in it and who it is for. Each of those is a separate
    // key; dropping one renders "undefined" rather than failing loudly.
    stripBundle(2000, [1500, 800], ['name' => 'Full Payload', 'vehicle_type' => 'motorcycle']);

    $page   = $this->actingAs(stripCustomer())->get('/customer/dashboard')->assertOk();
    $bundle = collect(bundlePayload($page))->firstWhere('name', 'Full Payload');

    expect($bundle)->not->toBeNull();
    expect(array_keys($bundle))->toContain(
        'id', 'name', 'price', 'list', 'saves', 'percent',
        'vehicle', 'includes', 'serviceIds', 'duration'
    );

    expect($bundle['price'])->toBe(2000.0);
    expect($bundle['list'])->toBe(2300.0);
    expect($bundle['saves'])->toBe(300.0);
    expect($bundle['includes'])->toHaveCount(2);
    expect($bundle['serviceIds'])->toHaveCount(2);

    // The restriction decides whether the booking will be accepted at all, so
    // a card that omits it sends the customer to a modal that rejects them.
    expect($bundle['vehicle'])->toBe('motorcycle');
});

it('puts the bundle strip on the booking screen, not only under Promos', function () {
    stripBundle(2000, [1500, 800]);

    $html = $this->actingAs(stripCustomer())->get('/customer/dashboard')->assertOk()->getContent();

    expect($html)->toContain('id="bundleStrip"');

    // Inside the Book a Service panel. Bundles were previously only under
    // Promos & Offers — a tab you reach after choosing a service.
    $panel = strpos($html, 'id="panel-services"');
    $strip = strpos($html, 'id="bundleStrip"');
    $next  = strpos($html, 'id="panel-vehicles"');

    expect($panel)->not->toBeFalse();
    expect($strip)->toBeGreaterThan($panel);
    expect($strip)->toBeLessThan($next);
});

it('draws both bundle panels from one builder', function () {
    // Promos & Offers and the strip must not grow separate copies of the
    // card markup: two copies is how the same bundle ends up showing two
    // different prices.
    $html = $this->actingAs(stripCustomer())->get('/customer/dashboard')->assertOk()->getContent();

    // Declared once, and the card's opening tag written once — a second
    // literal .promo-card root would mean a second copy of the markup.
    expect(substr_count($html, 'function bundleCard('))->toBe(1);
    expect(substr_count($html, '<div class="promo-card$'))->toBe(1);

    // And both panels go through it.
    expect($html)->toContain('BUNDLES.map(b => bundleCard(b)).join');
    expect($html)->toContain("BUNDLES.map(b => bundleCard(b, { compact: true })).join");
});

// ── Booking one ──────────────────────────────────────────────────────────

it('refuses a bundle whose run has ended', function () {
    // Same gate as the listing, checked again on the way in: a strip rendered
    // before midnight is still on screen after it.
    $bundle   = stripBundle(2000, [1500, 800], ['ends_at' => now()->subDay()]);
    $customer = stripCustomer();
    $vehicle  = stripVehicle($customer, 'STR 1111');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect(Booking::count())->toBe(0);
});

it('books a bundle the strip offered, at the bundle price', function () {
    $bundle   = stripBundle(2000, [1500, 800]);
    $customer = stripCustomer();
    $vehicle  = stripVehicle($customer, 'STR 2222');

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'bundle_id' => $bundle->id, 'vehicle_id' => $vehicle->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $booking = Booking::sole();
    expect($booking->bundle_id)->toBe($bundle->id);
    expect($booking->total_price)->toBe(2000.0);
    expect($booking->services)->toHaveCount(2);
});
