<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function recAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function recCustomer(string $name = 'Record Customer'): Customer
{
    $user = User::factory()->create([
        'role' => 'customer', 'name' => $name,
        'email' => strtolower(str_replace(' ', '', $name)).uniqid().'@gmail.com',
        'phone' => '09171234567',
    ]);

    return Customer::create(['user_id' => $user->id, 'phone' => '09171234567']);
}

function recBooking(Customer $customer): Booking
{
    $unique  = substr(str_replace('.', '', uniqid('', true)), -6);
    $vehicle = Vehicle::create([
        'user_id' => $customer->user_id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'REC '.$unique, 'color' => 'Red',
    ]);
    $service = Service::create(['name' => 'Rec '.$unique, 'price' => 1500, 'duration' => 45]);

    return Booking::create([
        'user_id' => $customer->user_id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->toDateString(), 'booking_time' => '10:00',
        'status' => 'confirmed', 'reference_number' => 'APX-'.$unique,
    ]);
}

// ── 5a: personal details are read-only for admins ───────────────────────

it('refuses an admin edit of a customers personal details', function () {
    $customer = recCustomer('Original Name');
    $user     = $customer->user;

    $this->actingAs(recAdmin())->put("/admin/customers/{$customer->id}", [
        'name'  => 'Changed By Admin',
        'email' => 'changed@gmail.com',
        'phone' => '09990000000',
    ])->assertRedirect(route('admin.customers.index'));

    // Refused on the server, not merely hidden in the form.
    $user->refresh();
    expect($user->name)->toBe('Original Name');
    expect($user->email)->not->toBe('changed@gmail.com');
    expect($user->phone)->toBe('09171234567');
});

it('says why rather than failing silently', function () {
    $customer = recCustomer();

    $this->actingAs(recAdmin())
        ->put("/admin/customers/{$customer->id}", ['name' => 'X', 'email' => 'x@gmail.com', 'phone' => '09170000000'])
        ->assertSessionHas('error');
});

// ── 5b: View and Delete ─────────────────────────────────────────────────

it('gives every row its own view and delete control', function () {
    $customer = recCustomer('Viewable Person');

    $response = $this->actingAs(recAdmin())->get('/admin/customers')->assertOk();

    // Both used to call openModal() with no id, so every row opened the same
    // empty panel.
    $response->assertSee('viewCustomer(this)', false);
    $response->assertSee('deleteCustomer(this)', false);
    $response->assertSee(route('admin.customers.destroy', $customer->id), false);
});

it('deletes a customer who has no bookings', function () {
    $customer = recCustomer('Deletable Person');
    $userId   = $customer->user_id;

    $this->actingAs(recAdmin())
        ->delete("/admin/customers/{$customer->id}")
        ->assertRedirect(route('admin.customers.index'))
        ->assertSessionHas('success');

    expect(Customer::find($customer->id))->toBeNull();
    expect(User::find($userId))->toBeNull();
    // Soft deleted, so recoverable.
    expect(User::withTrashed()->find($userId))->not->toBeNull();
});

it('refuses to delete a customer who has bookings', function () {
    $customer = recCustomer('Has History');
    recBooking($customer);

    $this->actingAs(recAdmin())
        ->delete("/admin/customers/{$customer->id}")
        ->assertRedirect(route('admin.customers.index'))
        ->assertSessionHas('error');

    // Nothing removed: the service history is the point.
    expect(Customer::find($customer->id))->not->toBeNull();
    expect(User::find($customer->user_id))->not->toBeNull();
    expect(Booking::where('user_id', $customer->user_id)->count())->toBe(1);
});

it('counts the bookings in the refusal', function () {
    $customer = recCustomer();
    recBooking($customer);
    recBooking($customer);

    $this->actingAs(recAdmin())->delete("/admin/customers/{$customer->id}");

    expect(session('error'))->toContain('2 bookings');
});

it('keeps deletion to admins', function () {
    $customer = recCustomer();

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->delete("/admin/customers/{$customer->id}")
        ->assertStatus(403);

    expect(Customer::find($customer->id))->not->toBeNull();
});

// ── 5c: exports carry no personal data ──────────────────────────────────

it('keeps email, phone and plate out of every export', function (string $route) {
    $customer = recCustomer('Exported Person');
    recBooking($customer);

    $response = $this->actingAs(recAdmin())->get($route)->assertOk();

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->not->toContain($customer->user->email);
    expect($body)->not->toContain('09171234567');
    expect($body)->not->toContain('REC ');
    expect($body)->not->toContain('Email');
    expect($body)->not->toContain('Phone');
    expect($body)->not->toContain('Plate');
})->with([
    '/admin/bookings/export',
    '/admin/bookings/cancelled/export',
    '/admin/customers/export',
]);

it('still exports the useful columns', function () {
    $customer = recCustomer('Exported Person');
    recBooking($customer);

    $response = $this->actingAs(recAdmin())->get('/admin/bookings/export')->assertOk();
    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toContain('Reference');
    expect($body)->toContain('Exported Person');
    expect($body)->toContain('Status');
});
