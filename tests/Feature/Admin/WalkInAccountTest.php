<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Two walk-ins with the same name must not collide.
 *
 * A walk-in who gives no email gets a stand-in address so the account has
 * somewhere to keep the booking. It was built from the name alone —
 * "Maria Santos" became mariasantos@apxautomai.local — and users.email is
 * unique, so the second Maria Santos returned a 500 from the New Booking form
 * with nothing created. Measured: 302 then 500.
 *
 * The account is keyed on the phone number, so that is what disambiguates.
 */

function wiAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function wiBook(User $admin, string $name, string $phone, string $plate)
{
    static $n = 1;

    $service = Service::create(['name' => 'Walk In '.$n++, 'price' => 1000, 'duration' => 60]);

    return test()->actingAs($admin)->post('/admin/bookings', [
        'customer_name'  => $name,
        'customer_phone' => $phone,
        'plate'          => $plate,
        'service_id'     => $service->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ]);
}

it('books a second customer with the same name', function () {
    $admin = wiAdmin();

    wiBook($admin, 'Maria Santos', '09170001111', 'WLK 0001')->assertRedirect();
    wiBook($admin, 'Maria Santos', '09170002222', 'WLK 0002')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::count())->toBe(2);
    expect(User::where('role', 'customer')->count())->toBe(2);

    // Two accounts, two addresses, both still placeholders.
    $emails = User::where('role', 'customer')->pluck('email');
    expect($emails->unique())->toHaveCount(2);

    foreach ($emails as $email) {
        expect($email)->toEndWith(BookingNotification::PLACEHOLDER_DOMAIN);
    }
});

it('keeps the readable address for the first of a name', function () {
    wiBook(wiAdmin(), 'Maria Santos', '09170001111', 'WLK 0011')->assertRedirect();

    // Only a collision makes it uglier. The common case stays legible in the
    // customer list.
    expect(User::where('role', 'customer')->sole()->email)
        ->toBe('mariasantos'.BookingNotification::PLACEHOLDER_DOMAIN);
});

it('reuses the account when the same phone books again', function () {
    $admin = wiAdmin();

    wiBook($admin, 'Maria Santos', '09170001111', 'WLK 0021')->assertRedirect();
    wiBook($admin, 'Maria Santos', '09170001111', 'WLK 0021')->assertRedirect();

    // firstOrCreate keys on the phone, so this is one customer with two
    // bookings, not two customers.
    expect(User::where('role', 'customer')->count())->toBe(1);
});

it('does not reuse an address held by a deleted account', function () {
    $admin = wiAdmin();

    wiBook($admin, 'Maria Santos', '09170001111', 'WLK 0031')->assertRedirect();

    $first = User::where('role', 'customer')->sole();
    $first->delete();

    // A soft-deleted row still occupies the unique index, so an address that
    // looks free is not. Same lesson as the plate lookup.
    wiBook($admin, 'Maria Santos', '09170003333', 'WLK 0032')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::withTrashed()->where('role', 'customer')->count())->toBe(2);
});

it('still takes a real address when one is given', function () {
    test()->actingAs(wiAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Maria Santos',
        'customer_phone' => '09170004444',
        'customer_email' => 'maria@gmail.com',
        'plate'          => 'WLK 0041',
        'service_id'     => Service::create(['name' => 'Real', 'price' => 1000, 'duration' => 60])->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ])->assertRedirect();

    expect(User::where('role', 'customer')->sole()->email)->toBe('maria@gmail.com');
});
