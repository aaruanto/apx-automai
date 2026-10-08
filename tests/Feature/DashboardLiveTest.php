<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * The read-only endpoints the dashboards poll. They must return exactly the
 * figures the page renders, stay cheap, and never expose one customer's
 * bookings to another.
 */

function liveBooking(User $customer, string $status = 'pending', int $price = 1500): Booking
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'LIV '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Live Service '.$unique, 'price' => $price, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->toDateString(), 'booking_time' => '10:00',
        'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $price, 'duration' => 45]);

    return $booking;
}

// ── Admin ────────────────────────────────────────────────────────────────

it('returns the same stats the admin dashboard renders', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    liveBooking($customer, 'pending');
    liveBooking($customer, 'pending');
    liveBooking($customer, 'completed');

    $admin = User::factory()->create(['role' => 'admin']);

    $page = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    $live = $this->actingAs($admin)->getJson('/admin/dashboard/live')->assertOk();

    // The poll must agree with the page, or the numbers would jump on first tick.
    expect($live->json('stats.today'))->toBe($page->viewData('todayBookings'));
    expect($live->json('stats.pending'))->toBe($page->viewData('pending'));
    expect($live->json('stats.this_week'))->toBe($page->viewData('thisWeek'));
    expect($live->json('stats.completed'))->toBe($page->viewData('completed'));
});

it('returns recent bookings with every service named', function () {
    $customer = User::factory()->create(['role' => 'customer', 'name' => 'Poll Customer']);
    $booking  = liveBooking($customer);
    $extra    = Service::create(['name' => 'Second Service', 'price' => 500, 'duration' => 20]);
    $booking->services()->attach($extra->id, ['price' => 500, 'duration' => 20]);

    $row = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->getJson('/admin/dashboard/live')->assertOk()
        ->json('bookings.0');

    expect($row['customer'])->toBe('Poll Customer');
    expect($row['services'])->toContain('Second Service');
    expect($row['label'])->toBe('Pending');
});

it('does not run a query per row', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    foreach (range(1, 6) as $i) {
        liveBooking($customer);
    }

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    DB::enableQueryLog();
    $this->getJson('/admin/dashboard/live')->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Four stat counts plus the row fetch and its eager loads. A per-row
    // lookup for customer, staff or services would blow well past this.
    expect($count)->toBeLessThan(15);
});

it('keeps the admin feed to admins', function () {
    $this->getJson('/admin/dashboard/live')->assertStatus(401);

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->getJson('/admin/dashboard/live')->assertStatus(403);
});

// ── Customer ─────────────────────────────────────────────────────────────

it('returns the customer their own figures', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    liveBooking($customer, 'pending');
    liveBooking($customer, 'confirmed');
    liveBooking($customer, 'completed', 2000);

    $live = $this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk();

    expect($live->json('stats.upcoming'))->toBe(2);
    expect($live->json('stats.completed'))->toBe(1);
    expect($live->json('stats.total'))->toBe(3);
    // Completed work only, from the price snapshots.
    expect((float) $live->json('stats.spent'))->toBe(2000.0);
});

it('never returns another customers bookings', function () {
    $mine   = User::factory()->create(['role' => 'customer']);
    $theirs = User::factory()->create(['role' => 'customer']);

    liveBooking($mine);
    $other = liveBooking($theirs);

    $live = $this->actingAs($mine)->getJson('/customer/dashboard/live')->assertOk();

    expect($live->json('stats.total'))->toBe(1);
    expect(collect($live->json('bookings'))->pluck('reference'))
        ->not->toContain($other->reference_number);
});

it('keeps the customer feed behind auth', function () {
    $this->getJson('/customer/dashboard/live')->assertStatus(401);
});

it('reports a timestamp so the page can show when it last updated', function () {
    $live = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->getJson('/admin/dashboard/live')->assertOk();

    expect($live->json('updated_at'))->not->toBeNull();
});

it('works on an empty database', function () {
    $live = $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->getJson('/admin/dashboard/live')->assertOk();

    expect($live->json('stats.today'))->toBe(0);
    expect($live->json('bookings'))->toBe([]);
});
