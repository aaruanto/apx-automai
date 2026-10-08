<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * In-app notifications. Database channel only: the brief scopes SMS and email
 * out of this, and mail is not configured yet in any case.
 */

function notifAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function notifBooking(User $customer, string $status = 'pending'): Booking
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -6);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'NTF '.$unique, 'color' => 'Red',
    ]);
    $service = Service::create(['name' => 'Notif Service '.$unique, 'price' => 1500, 'duration' => 45]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->addDay()->toDateString(), 'booking_time' => '10:00',
        'duration' => 45, 'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);
    $booking->services()->attach($service->id, ['price' => 1500, 'duration' => 45]);

    return $booking;
}

// ── Triggers ─────────────────────────────────────────────────────────────

it('tells admins when a customer books', function () {
    $admin    = notifAdmin();
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'NEW 1234', 'color' => 'Red',
    ]);
    $service = Service::create(['name' => 'Oil Change', 'price' => 1500, 'duration' => 45]);

    $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [$service->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => today()->addDay()->toDateString(),
        'booking_time' => '11:00',
    ])->assertOk();

    expect($admin->unreadNotifications()->count())->toBe(1);
    expect($admin->notifications()->first()->data['title'])->toBe('New booking request');
});

it('tells the customer when their booking is confirmed', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = notifBooking($customer, 'pending');

    $this->actingAs(notifAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => [$booking->service_id],
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => 'confirmed',
    ])->assertSessionHasNoErrors();

    expect($customer->unreadNotifications()->count())->toBe(1);
    expect($customer->notifications()->first()->data['title'])->toBe('Booking confirmed');
});

it('tells the customer when their booking is cancelled, with the reason', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = notifBooking($customer, 'confirmed');

    $this->actingAs(notifAdmin())
        ->patchJson("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Parts delayed'])
        ->assertOk();

    $data = $customer->notifications()->first()->data;
    expect($data['title'])->toBe('Booking cancelled');
    // The reason is the point: a cancellation with no explanation is the
    // complaint this closes.
    expect($data['body'])->toContain('Parts delayed');
});

it('tells the customer when their booking is rescheduled, naming both slots', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = notifBooking($customer, 'confirmed');
    $oldDate  = $booking->booking_date;

    $this->actingAs(notifAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => [$booking->service_id],
        'booking_date' => today()->addDays(5)->toDateString(),
        'booking_time' => '14:00',
        'status'       => 'confirmed',
    ])->assertSessionHasNoErrors();

    $body = collect($customer->notifications)->firstWhere('data.event', BookingNotification::BOOKING_RESCHEDULED)->data['body'];
    expect($body)->toContain($oldDate);
    expect($body)->toContain(today()->addDays(5)->toDateString());
});

it('tells the customer when the service is completed', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = notifBooking($customer, 'in_progress');

    $this->actingAs(notifAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => [$booking->service_id],
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => 'completed',
    ])->assertSessionHasNoErrors();

    expect($customer->notifications()->first()->data['title'])->toBe('Service completed');
});

it('does not notify when nothing actually changed', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = notifBooking($customer, 'confirmed');

    // Saving the form untouched must not spam the customer.
    $this->actingAs(notifAdmin())->put("/admin/bookings/{$booking->id}", [
        'service_ids'  => [$booking->service_id],
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => 'confirmed',
    ])->assertSessionHasNoErrors();

    expect($customer->notifications()->count())->toBe(0);
});

// ── Reading them ─────────────────────────────────────────────────────────

it('serves the feed with an unread count', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $customer->notify(BookingNotification::confirmed(notifBooking($customer)));

    $feed = $this->actingAs($customer)->getJson('/notifications/feed')->assertOk();

    expect($feed->json('unread'))->toBe(1);
    expect($feed->json('items.0.title'))->toBe('Booking confirmed');
    expect($feed->json('items.0.unread'))->toBeTrue();
});

it('marks one read', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $customer->notify(BookingNotification::confirmed(notifBooking($customer)));
    $id = $customer->notifications()->first()->id;

    $this->actingAs($customer)->postJson("/notifications/{$id}/read")
        ->assertOk()->assertJson(['success' => true, 'unread' => 0]);

    expect($customer->unreadNotifications()->count())->toBe(0);
});

it('marks all read', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    foreach (range(1, 3) as $i) {
        $customer->notify(BookingNotification::confirmed(notifBooking($customer)));
    }

    $this->actingAs($customer)->postJson('/notifications/read-all')->assertOk();

    expect($customer->unreadNotifications()->count())->toBe(0);
});

it('will not let one user read anothers notification', function () {
    $mine   = User::factory()->create(['role' => 'customer']);
    $theirs = User::factory()->create(['role' => 'customer']);
    $theirs->notify(BookingNotification::confirmed(notifBooking($theirs)));
    $id = $theirs->notifications()->first()->id;

    $this->actingAs($mine)->postJson("/notifications/{$id}/read")->assertStatus(404);

    expect($theirs->unreadNotifications()->count())->toBe(1);
});

it('shows only your own notifications on the full page', function () {
    $mine   = User::factory()->create(['role' => 'customer']);
    $theirs = User::factory()->create(['role' => 'customer']);
    $mine->notify(BookingNotification::confirmed(notifBooking($mine)));
    $theirs->notify(BookingNotification::cancelled(notifBooking($theirs), 'Private reason'));

    $this->actingAs($mine)->get('/notifications')->assertOk()
        ->assertSee('Booking confirmed')
        ->assertDontSee('Private reason');
});

it('keeps notifications behind auth', function () {
    $this->getJson('/notifications/feed')->assertStatus(401);
    $this->get('/notifications')->assertRedirect('/login');
});

// ── Folded into the existing poll, not a second one ──────────────────────

it('reports the unread count on the dashboard poll', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $customer->notify(BookingNotification::confirmed(notifBooking($customer)));

    expect($this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk()->json('unread'))->toBe(1);

    $admin = notifAdmin();
    $admin->notify(BookingNotification::requested(notifBooking($customer)));

    expect($this->actingAs($admin)->getJson('/admin/dashboard/live')->assertOk()->json('unread'))->toBe(1);
});
