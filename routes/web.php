<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Http\Controllers\Admin\MessageTemplateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestBookingController;
use App\Models\Service;

// ── Public routes ──────────────────────────────────────────────────────────
Route::get('/', function () {
    $services = Service::where('is_active', true)
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    return view('index', compact('services'));
});
Route::get('/about',       fn() => view('about'));
Route::get('/booking',     fn() => view('booking'));
Route::get('/contact',     fn() => view('contact'));
Route::get('/service',     fn() => view('service'));
Route::get('/terms',       fn() => view('terms'))->name('terms');

// ── Auth profile (Breeze default) ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Admin ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Bookings ───────────────────────────────────────────────────────────
    // Static sub-routes MUST come before the {id} wildcard routes
    Route::get('/bookings/export',            [BookingController::class, 'export'])   ->name('bookings.export');
    Route::get('/bookings/cancelled/export',  [BookingController::class, 'exportCancelled'])->name('bookings.cancelled.export');
    Route::get('/bookings/schedule',          [BookingController::class, 'schedule']) ->name('bookings.schedule');
    Route::get('/bookings/cancelled',         [BookingController::class, 'cancelled'])->name('bookings.cancelled');
    Route::get('/bookings/create',            [BookingController::class, 'create'])   ->name('bookings.create');
    Route::post('/bookings',                  [BookingController::class, 'store'])    ->name('bookings.store');
    Route::get('/bookings',                   [BookingController::class, 'index'])    ->name('bookings.index');
    Route::get('/bookings/{id}/edit',         [BookingController::class, 'edit'])     ->name('bookings.edit');
    Route::put('/bookings/{id}',              [BookingController::class, 'update'])   ->name('bookings.update');
    Route::get('/bookings/{id}/rebook',       [BookingController::class, 'rebook'])   ->name('bookings.rebook');
    Route::delete('/bookings/{id}',           [BookingController::class, 'destroy'])  ->name('bookings.destroy');
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('/bookings/{id}/arrive', [BookingController::class, 'arrive'])->name('bookings.arrive');
    Route::patch('/bookings/{id}/staff',  [BookingController::class, 'assignStaff'])->name('bookings.staff');
    // ── Customers ──────────────────────────────────────────────────────────
    Route::get('/customers/export',           [CustomerController::class, 'export'])  ->name('customers.export');
    Route::get('/customers/create',           [CustomerController::class, 'create'])  ->name('customers.create');
    Route::post('/customers',                 [CustomerController::class, 'store'])   ->name('customers.store');
    Route::get('/customers',                  [CustomerController::class, 'index'])   ->name('customers.index');
    Route::get('/customers/{id}/edit',        [CustomerController::class, 'edit'])    ->name('customers.edit');
    Route::put('/customers/{id}',             [CustomerController::class, 'update'])  ->name('customers.update');
    Route::delete('/customers/{id}',          [CustomerController::class, 'destroy']) ->name('customers.destroy');

    // ── Reports ────────────────────────────────────────────────────────────
    Route::get('/reports',                    [ReportController::class, 'index'])     ->name('reports.index');
    Route::get('/reports/export',             [ReportController::class, 'export'])    ->name('reports.export');
    // ── Profile ────────────────────────────────────────────────────────────
    Route::get('/profile',                    [AdminProfileController::class, 'index'])         ->name('profile');
    Route::put('/profile',                    [AdminProfileController::class, 'update'])         ->name('profile.update');
    Route::put('/profile/password',           [AdminProfileController::class, 'updatePassword']) ->name('profile.password');

    // ── Settings ───────────────────────────────────────────────────────────
    Route::get('/settings',                   [SettingsController::class, 'index'])          ->name('settings');
    Route::put('/settings/general',           [SettingsController::class, 'updateGeneral'])  ->name('settings.general');
    Route::put('/settings/booking',           [SettingsController::class, 'updateBooking'])  ->name('settings.booking');
    Route::put('/settings/system',            [SettingsController::class, 'updateSystem'])   ->name('settings.system');
    Route::post('/settings/staff',            [SettingsController::class, 'storeStaff'])     ->name('settings.staff.store');
    Route::put('/settings/staff/{id}',        [SettingsController::class, 'updateStaff'])    ->name('settings.staff.update');
    Route::delete('/settings/staff/{id}',     [SettingsController::class, 'destroyStaff'])   ->name('settings.staff.destroy');
    Route::post('/settings/services',         [SettingsController::class, 'storeService'])   ->name('settings.services.store');
    Route::put('/settings/services/{id}',     [SettingsController::class, 'updateService'])  ->name('settings.services.update');
    Route::delete('/settings/services/{id}',  [SettingsController::class, 'destroyService']) ->name('settings.services.destroy');

    // ── Message Templates ───────────────────────────────────────────────────────────
    Route::get('/templates',       [MessageTemplateController::class, 'index'])   ->name('templates.index');
    Route::post('/templates/save', [MessageTemplateController::class, 'save'])    ->name('templates.save');
    Route::post('/templates/test', [MessageTemplateController::class, 'sendTest'])->name('templates.test');

});

// ── Staff ──────────────────────────────────────────────────────────────────
// Staff shares the admin dashboard view, so it needs the same data — reuse the
// controller instead of rendering the view bare (that left every stat/chart
// variable undefined).
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');
});

// ── Customer ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:customer', 'maintenance'])->group(function () {
    Route::get('/customer/dashboard',              [CustomerDashboard::class, 'index'])          ->name('customer.dashboard');
    Route::post('/customer/bookings',              [CustomerDashboard::class, 'store'])           ->name('customer.bookings.store');
    Route::patch('/customer/bookings/{id}/cancel', [CustomerDashboard::class, 'cancelBooking'])   ->name('customer.bookings.cancel');
    Route::post('/customer/vehicles',              [CustomerDashboard::class, 'storeVehicle'])    ->name('customer.vehicles.store');
    Route::patch('/customer/vehicles/{id}',        [CustomerDashboard::class, 'updateVehicle'])   ->name('customer.vehicles.update');
    Route::delete('/customer/vehicles/{id}',       [CustomerDashboard::class, 'destroyVehicle'])  ->name('customer.vehicles.destroy');
    Route::patch('/customer/vehicles/{id}/primary',[CustomerDashboard::class, 'setPrimaryVehicle'])->name('customer.vehicles.setPrimary');
    Route::patch('/customer/profile',              [CustomerDashboard::class, 'updateProfile'])   ->name('customer.profile.update');
    Route::get('/customer/account/deletion-status', [CustomerDashboard::class, 'accountDeletionStatus'])->name('customer.account.status');
    Route::delete('/customer/account',             [CustomerDashboard::class, 'destroyAccount'])      ->name('customer.account.destroy');
});

// ── Guest Booking (public — no auth required) ──────────────────────────────
    Route::post('/booking/guest', [GuestBookingController::class, 'store'])->middleware('maintenance')->name('booking.guest');

// ── Availability picker API (public — counts only, no customer data) ──────
    Route::get('/booking/availability', \App\Http\Controllers\AvailabilityController::class)->name('booking.availability');


require __DIR__.'/auth.php';