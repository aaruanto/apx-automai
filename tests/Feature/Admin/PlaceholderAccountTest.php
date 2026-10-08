<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Accounts the shop creates on a customer's behalf used to be given the literal
 * password "password", alongside an email derived from the customer's name.
 * Anyone who worked out the pattern could sign in as that customer and read
 * their bookings and vehicles. These lock the replacement in place.
 */

function staffAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('does not give a walk-in booking account a guessable password', function () {
    $service = Service::create(['name' => 'Walk-in Service', 'price' => 1200, 'duration' => 30]);

    $this->actingAs(staffAdmin())->post('/admin/bookings', [
        'customer_name'  => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'plate'          => 'WLK 1234',
        'service_id'     => $service->id,
        'booking_date'   => today()->toDateString(),
        'booking_time'   => '10:00',
    ]);

    $user = User::where('phone', '09171234567')->first();
    expect($user)->not->toBeNull();

    foreach (['password', 'Password', 'password123', 'juandelacruz'] as $guess) {
        expect(Hash::check($guess, $user->password))->toBeFalse("'{$guess}' should not work");
    }
});

it('does not give an admin-created customer a guessable password', function () {
    $this->actingAs(staffAdmin())->post('/admin/customers', [
        'name'  => 'Maria Santos',
        'email' => 'maria@example.com',
        'phone' => '09981234567',
        'plate' => 'MAR 5678',
    ]);

    $user = User::where('email', 'maria@example.com')->first();
    expect($user)->not->toBeNull();

    expect(Hash::check('password', $user->password))->toBeFalse();
});

it('leaves a staff-created account unverified so it can still be claimed', function () {
    // The registration flow lets someone take over an unverified account by
    // registering with the same email. That is how these accounts become
    // usable, so the flag must stay null.
    $this->actingAs(staffAdmin())->post('/admin/customers', [
        'name'  => 'Maria Santos',
        'email' => 'maria@example.com',
        'phone' => '09981234567',
        'plate' => 'MAR 5678',
    ]);

    expect(User::where('email', 'maria@example.com')->first()->email_verified_at)->toBeNull();
});
