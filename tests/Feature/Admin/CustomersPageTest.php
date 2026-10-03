<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the customer list when a customer has no surviving user', function () {
    // Account deletion soft-deletes the user but leaves the customer row, so
    // $c->user resolves to null. ?? covers a null property read but not a null
    // method call, and $c->user->vehicles() took the whole page down with a 500.
    $user = User::factory()->create(['role' => 'customer', 'name' => 'Departed Customer']);

    Vehicle::create([
        'user_id' => $user->id, 'make' => 'Honda', 'model' => 'Civic',
        'year' => 2019, 'plate_number' => 'GON 1234', 'color' => 'White',
    ]);

    Customer::create(['user_id' => $user->id]);

    $user->delete(); // soft delete

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')
        ->assertOk();
});

it('still renders a customer whose user is intact', function () {
    $user = User::factory()->create(['role' => 'customer', 'name' => 'Present Customer']);
    Customer::create(['user_id' => $user->id]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/customers')
        ->assertOk()
        ->assertSee('Present Customer');
});
