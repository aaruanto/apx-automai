<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The summary cards open the bookings they count.
 *
 * Four cards sit at the top of All Bookings — Confirmed, Pending, In Progress,
 * Cancelled — each with a live count. They were plain divs. They look exactly
 * like the dashboard's cards, which have always been links, so the one screen
 * showing a count per status was the one screen where you could not click
 * through to see them.
 *
 * Almost none of this is new. ?status= has been read and preselected on this
 * page for some time, and the filtering works; what was missing was anything
 * pointing at it from here. That is the whole of the "filtered views per
 * status" request — no new pages, no duplicated tables.
 *
 * Cancelled is the exception and goes to its own page. That page is not this
 * table with a filter on it: it carries the cancellation reason, who
 * cancelled and when, and offers Rebook and Delete permanently. Sending the
 * card to ?status=cancelled would hide the better view behind the lesser one.
 */

function statusCardAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function statusCardBooking(string $status, string $date): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'SCL '.$unique,
    ]);

    $service = Service::create(['name' => 'Card Service '.$unique, 'price' => 900, 'duration' => 30]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => $date, 'booking_time' => '10:00', 'status' => $status,
        'duration' => 30, 'reference_number' => 'SCL-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => $service->price]);

    return $booking;
}

it('makes each summary card a link to what it counts', function () {
    $html = $this->actingAs(statusCardAdmin())->get('/admin/bookings')->assertOk()->getContent();

    foreach (['confirmed', 'pending', 'in_progress'] as $status) {
        expect($html)->toContain('href="'.route('admin.bookings.index', ['status' => $status]).'"');
    }

    // And they are links, not the divs they used to be.
    expect($html)->toContain('class="stat-mini"');
});

it('sends the cancelled card to the page that has more to show', function () {
    $html = $this->actingAs(statusCardAdmin())->get('/admin/bookings')->assertOk()->getContent();

    expect($html)->toContain('href="'.route('admin.bookings.cancelled').'"');
    expect($html)->not->toContain(route('admin.bookings.index', ['status' => 'cancelled']));
});

it('marks the card whose filter the list is showing', function () {
    $dates = nextWeekdays(2);
    statusCardBooking('pending', $dates[0]);
    statusCardBooking('completed', $dates[1]);

    $admin = statusCardAdmin();

    // Matched on the card's class attribute, not the bare name: the rule that
    // styles it lives in the layout's own <style> block, so the name appears
    // in the markup of every page whether a card is active or not.
    $plain = $this->actingAs($admin)->get('/admin/bookings')->assertOk()->getContent();
    expect($plain)->not->toContain('class="stat-mini stat-mini-active"');

    $filtered = $this->get('/admin/bookings?status=pending')->assertOk()->getContent();
    expect($filtered)->toContain('class="stat-mini stat-mini-active"');
});

it('still preselects the dropdown from the same value', function () {
    $html = $this->actingAs(statusCardAdmin())
        ->get('/admin/bookings?status=in_progress')->assertOk()->getContent();

    // One read of request('status') now serves both the cards and the filter
    // bar, so they cannot disagree about which filter is on.
    expect($html)->toMatch('/<option value="in_progress"\s+selected>/');
});

it('keeps the card styling in the shared layout', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

    // The next screen that shows a count per status should look and behave
    // the same, rather than inlining its own.
    expect($layout)->toContain('.stat-mini {');
    expect($layout)->toContain('.stat-mini-active {');
});
