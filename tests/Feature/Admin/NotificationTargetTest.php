<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A notification points at the booking it is about.
 *
 * Every booking notification linked to the bare list URL, so "a booking was
 * cancelled" dropped the admin at the top of every booking there has ever
 * been with nothing to say which one it meant. Opening a notification also
 * marked it read, which took away the unread tint — the only thing that
 * distinguished it — so the row just acted on became less visible than the
 * ones that had not been, and coming back left you hunting for your place.
 *
 * The reference was in the payload the whole time, sitting beside the link
 * that ignored it. It is resolved at read time rather than written into the
 * payload, so the notifications already in the table — the ones an admin is
 * reading today — get the same treatment as the next one sent.
 */

function notifTargetAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function notifTargetBooking(): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'NTF '.$unique,
    ]);

    $service = Service::create(['name' => 'Notify Service '.$unique, 'price' => 1200, 'duration' => 45]);

    return Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00', 'status' => 'pending',
        'duration' => 45, 'reference_number' => 'NTF-'.$unique,
    ]);
}

it('names the booking in the link', function () {
    $link = BookingNotification::linkFor([
        'link'      => 'http://localhost/admin/bookings',
        'reference' => 'BK-0029',
    ]);

    expect($link)->toBe('http://localhost/admin/bookings?ref=BK-0029');
});

it('repairs the links of notifications already sent', function () {
    $admin   = notifTargetAdmin();
    $booking = notifTargetBooking();

    $admin->notify(BookingNotification::requested($booking));

    // The payload still holds whatever link it was written with.
    $stored = $admin->notifications()->first();
    expect($stored->data)->toHaveKey('reference', $booking->reference_number);

    // And it resolves to one that names the booking.
    expect(BookingNotification::linkFor($stored->data))
        ->toContain('ref='.$booking->reference_number);
});

it('leaves a link alone when there is nothing to point at', function () {
    expect(BookingNotification::linkFor(['link' => 'http://localhost/admin/bookings']))
        ->toBe('http://localhost/admin/bookings');

    expect(BookingNotification::linkFor([]))->toBeNull();
});

it('does not add the reference twice', function () {
    $once = BookingNotification::linkFor([
        'link' => 'http://localhost/admin/bookings?ref=BK-1', 'reference' => 'BK-1',
    ]);

    expect(substr_count($once, 'ref=' ))->toBe(1);
});

it('sends the admin from the notifications page to the right row', function () {
    $admin   = notifTargetAdmin();
    $booking = notifTargetBooking();
    $admin->notify(BookingNotification::requested($booking));

    $html = $this->actingAs($admin)->get('/notifications')->assertOk()->getContent();

    expect($html)->toContain('ref='.$booking->reference_number);
});

it('sends the admin from the bell to the same row', function () {
    $admin   = notifTargetAdmin();
    $booking = notifTargetBooking();
    $admin->notify(BookingNotification::requested($booking));

    $this->actingAs($admin)->getJson('/notifications/feed')
        ->assertOk()
        ->assertJsonPath('items.0.link', fn ($link) => str_contains($link, 'ref='.$booking->reference_number));
});

it('gives the bookings list something to mark the row by', function () {
    $admin   = notifTargetAdmin();
    $booking = notifTargetBooking();

    $html = $this->actingAs($admin)->get('/admin/bookings')->assertOk()->getContent();

    // The row is addressable by reference, and the page knows to look for one.
    expect($html)->toContain('data-ref="'.$booking->reference_number.'"');
    expect($html)->toContain("new URLSearchParams(location.search).get('ref')");
    expect($html)->toContain("row.classList.add('row-focus')");
});

it('keeps the mark a notification lands on in the shared layout', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

    // Beside the other table rules, so the cancelled list and any future one
    // mark a row the same way rather than inventing their own.
    expect($layout)->toContain('.apx-table tbody tr.row-focus');
});

it('marks the notification you opened so returning shows your place', function () {
    $admin = notifTargetAdmin();
    $admin->notify(BookingNotification::requested(notifTargetBooking()));

    $html = $this->actingAs($admin)->get('/notifications')->assertOk()->getContent();

    // Read removes the unread tint, so a separate marker is what survives it.
    expect($html)->toContain('.n-row.opened');
    expect($html)->toContain("localStorage.setItem(OPENED_KEY");
});
