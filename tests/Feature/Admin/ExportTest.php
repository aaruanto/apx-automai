<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The admin Export buttons were decorative markup with no handler. These cover
 * the endpoints behind them: correct headers, real rows, and no access for
 * anyone outside the admin role.
 */

function exportAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function exportBooking(string $status = 'confirmed'): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = User::factory()->create(['role' => 'customer', 'name' => 'Export Tester']);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2021, 'plate_number' => 'EXP '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create(['name' => 'Export Service '.$unique, 'price' => 1500, 'duration' => 45]);

    return Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => today()->toDateString(), 'booking_time' => '10:00',
        'status' => $status, 'reference_number' => 'APX-'.$unique,
    ]);
}

/** streamDownload defers output, so the body only exists once the callback runs. */
function csvBody($response): string
{
    ob_start();
    $response->sendContent();

    return ob_get_clean();
}

it('exports bookings as csv', function () {
    exportBooking('confirmed');

    $response = $this->actingAs(exportAdmin())->get('/admin/bookings/export')->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $body = csvBody($response);

    // The BOM matters: without it Excel reads the peso sign and accented
    // names using the system codepage and mangles them.
    expect($body)->toStartWith("\xEF\xBB\xBF");
    expect($body)->toContain('Reference,Customer,Email');
    expect($body)->toContain('Export Tester');
});

it('exports only cancelled bookings on the cancelled export', function () {
    exportBooking('confirmed');
    $cancelled = exportBooking('cancelled');

    $body = csvBody($this->actingAs(exportAdmin())->get('/admin/bookings/cancelled/export')->assertOk());

    expect($body)->toContain($cancelled->reference_number);
    expect(substr_count($body, "\n"))->toBe(2); // header + the one cancelled row
});

it('exports customers as csv', function () {
    $booking = exportBooking();
    Customer::create(['user_id' => $booking->user_id, 'vehicle_id' => $booking->vehicle_id]);

    $body = csvBody($this->actingAs(exportAdmin())->get('/admin/customers/export')->assertOk());

    expect($body)->toContain('Customer,Email,Phone,Vehicle,Plate');
    expect($body)->toContain('Export Tester');
});

it('exports the report summary for the selected period', function () {
    exportBooking('confirmed');

    $response = $this->actingAs(exportAdmin())->get('/admin/reports/export?period=90')->assertOk();
    $body = csvBody($response);

    expect($body)->toContain('Period,"Total Bookings",Confirmed,Cancelled');
    expect($body)->toContain(now()->format('F Y'));
});

it('names the download with the current date', function () {
    $response = $this->actingAs(exportAdmin())->get('/admin/bookings/export')->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toContain('bookings-'.now()->format('Y-m-d').'.csv');
});

it('survives an empty database', function (string $route) {
    $body = csvBody($this->actingAs(exportAdmin())->get($route)->assertOk());

    // Header row only, but still a valid file rather than an error.
    expect(trim($body))->not->toBe('');
})->with([
    '/admin/bookings/export',
    '/admin/bookings/cancelled/export',
    '/admin/customers/export',
    '/admin/reports/export',
]);

it('is closed to customers and guests', function (string $route) {
    $this->get($route)->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get($route)
        ->assertStatus(403);
})->with([
    '/admin/bookings/export',
    '/admin/bookings/cancelled/export',
    '/admin/customers/export',
    '/admin/reports/export',
]);
