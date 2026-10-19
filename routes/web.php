<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BundleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Http\Controllers\Admin\MechanicController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\MaintenanceRuleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestBookingController;
use App\Models\Service;

// ── Public routes ──────────────────────────────────────────────────────────
Route::get('/', function () {
    $services = Service::where('is_active', true)
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    // Only bundles a visitor could actually book today — switched on and
    // inside their run dates.
    // Cheapest first, so the packages read as a ladder — Basic, Standard,
    // Major. Alphabetical put Major in the middle, which tells a customer
    // nothing about how the three relate to each other.
    $bundles = \App\Models\Bundle::with('services')->bookable()->orderBy('price')->get();

    return view('index', compact('services', 'bundles'));
});
Route::get('/about',       fn() => view('about'));
Route::get('/booking',     fn() => view('booking'));
Route::get('/contact',     fn() => view('contact'));
Route::get('/service',     fn() => view('service'));
Route::get('/terms',       fn() => view('terms'))->name('terms');
Route::get('/privacy',     fn() => view('privacy'))->name('privacy');

// ── Auth profile (Breeze default) ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Notifications (any signed-in user: admin or customer) ────────────────
Route::middleware('auth')->group(function () {
    Route::get('/notifications',                [\App\Http\Controllers\NotificationController::class, 'index'])  ->name('notifications.index');
    Route::get('/notifications/feed',           [\App\Http\Controllers\NotificationController::class, 'feed'])   ->name('notifications.feed');
    Route::post('/notifications/{id}/read',     [\App\Http\Controllers\NotificationController::class, 'read'])   ->name('notifications.read');
    Route::post('/notifications/read-all',      [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.readAll');
});

// ── Admin ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

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
    Route::patch('/bookings/{id}/mechanic', [BookingController::class, 'assignMechanic'])->name('bookings.mechanic');
    // ── Customers ──────────────────────────────────────────────────────────
    Route::get('/customers/export',           [CustomerController::class, 'export'])  ->name('customers.export');
    Route::get('/customers/create',           [CustomerController::class, 'create'])  ->name('customers.create');
    Route::post('/customers',                 [CustomerController::class, 'store'])   ->name('customers.store');
    Route::get('/customers',                  [CustomerController::class, 'index'])   ->name('customers.index');
    Route::get('/customers/{id}/edit',        [CustomerController::class, 'edit'])    ->name('customers.edit');
    Route::put('/customers/{id}',             [CustomerController::class, 'update'])  ->name('customers.update');
    Route::post('/customers/{id}/points',  [CustomerController::class, 'adjustPoints'])->name('customers.points');
    Route::delete('/customers/{id}',          [CustomerController::class, 'destroy']) ->name('customers.destroy');

    // ── Reports ────────────────────────────────────────────────────────────
    Route::get('/reports',                    [ReportController::class, 'index'])     ->name('reports.index');
    Route::get('/reports/export',             [ReportController::class, 'export'])    ->name('reports.export');
    // ── Profile ────────────────────────────────────────────────────────────
    Route::get('/profile',                    [AdminProfileController::class, 'index'])         ->name('profile');
    Route::put('/profile',                    [AdminProfileController::class, 'update'])         ->name('profile.update');
    Route::put('/profile/password',           [AdminProfileController::class, 'updatePassword']) ->name('profile.password')->middleware('throttle:6,1');

    // ── Settings ───────────────────────────────────────────────────────────
    Route::get('/settings',                   [SettingsController::class, 'index'])          ->name('settings');
    Route::put('/settings/general',           [SettingsController::class, 'updateGeneral'])  ->name('settings.general');
    Route::put('/settings/booking',           [SettingsController::class, 'updateBooking'])  ->name('settings.booking');
    Route::put('/settings/loyalty',          [SettingsController::class, 'updateLoyalty'])  ->name('settings.loyalty');
    Route::put('/settings/system',            [SettingsController::class, 'updateSystem'])   ->name('settings.system');
    Route::post('/settings/services',         [SettingsController::class, 'storeService'])   ->name('settings.services.store');
    Route::put('/settings/services/{id}',     [SettingsController::class, 'updateService'])  ->name('settings.services.update');
    Route::delete('/settings/services/{id}',  [SettingsController::class, 'destroyService']) ->name('settings.services.destroy');

    // ── Message Templates ───────────────────────────────────────────────────────────
    Route::get('/templates',       [MessageTemplateController::class, 'index'])   ->name('templates.index');
    Route::post('/templates/save', [MessageTemplateController::class, 'save'])    ->name('templates.save');
    Route::post('/templates/test', [MessageTemplateController::class, 'sendTest'])->name('templates.test');

    // ── Bundles ─────────────────────────────────────────────────────────────
    // Several services sold together for less. Switched off rather than
    // deleted, because bookings reference the bundle they were sold under.
    Route::get('/bundles',               [BundleController::class, 'index'])  ->name('bundles.index');
    Route::post('/bundles',              [BundleController::class, 'store'])  ->name('bundles.store');
    Route::put('/bundles/{id}',          [BundleController::class, 'update']) ->name('bundles.update');
    Route::patch('/bundles/{id}/toggle', [BundleController::class, 'toggle']) ->name('bundles.toggle');

    // ── Service intervals ───────────────────────────────────────────────────
    // What counts as "due for maintenance". These drive the service-due
    // reminders, so the shop decides the intervals rather than the developer.
    // Switched off rather than deleted, because reminders already sent
    // reference the rule that raised them.
    Route::get('/maintenance',                [MaintenanceRuleController::class, 'index'])  ->name('maintenance.index');
    Route::post('/maintenance',               [MaintenanceRuleController::class, 'store'])  ->name('maintenance.store');
    Route::put('/maintenance/{id}',           [MaintenanceRuleController::class, 'update']) ->name('maintenance.update');
    Route::patch('/maintenance/{id}/toggle',  [MaintenanceRuleController::class, 'toggle']) ->name('maintenance.toggle');

    // ── Mechanics ───────────────────────────────────────────────────────────
    // Records, not accounts: a name and a contact number so work can be
    // assigned and someone can be rung about it. There is no mechanic login
    // and no staff portal; the only roles are admin and customer.
    Route::get('/mechanics',                 [MechanicController::class, 'index'])  ->name('mechanics.index');
    Route::post('/mechanics',                [MechanicController::class, 'store'])  ->name('mechanics.store');
    Route::put('/mechanics/{id}',            [MechanicController::class, 'update']) ->name('mechanics.update');
    Route::patch('/mechanics/{id}/toggle',   [MechanicController::class, 'toggle']) ->name('mechanics.toggle');

});

// ── Customer ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:customer', 'maintenance'])->group(function () {
    Route::get('/customer/dashboard',              [CustomerDashboard::class, 'index'])          ->name('customer.dashboard');
    Route::get('/customer/dashboard/live',         [CustomerDashboard::class, 'live'])           ->name('customer.dashboard.live');
    Route::post('/customer/bookings',              [CustomerDashboard::class, 'store'])           ->name('customer.bookings.store');
    Route::patch('/customer/bookings/{id}/cancel', [CustomerDashboard::class, 'cancelBooking'])   ->name('customer.bookings.cancel');
    Route::post('/customer/vehicles',              [CustomerDashboard::class, 'storeVehicle'])    ->name('customer.vehicles.store');
    Route::patch('/customer/vehicles/{id}',        [CustomerDashboard::class, 'updateVehicle'])   ->name('customer.vehicles.update');
    Route::delete('/customer/vehicles/{id}',       [CustomerDashboard::class, 'destroyVehicle'])  ->name('customer.vehicles.destroy');
    Route::patch('/customer/vehicles/{id}/primary',[CustomerDashboard::class, 'setPrimaryVehicle'])->name('customer.vehicles.setPrimary');
    Route::patch('/customer/profile',              [CustomerDashboard::class, 'updateProfile'])   ->name('customer.profile.update');
    Route::put('/customer/password',              [CustomerDashboard::class, 'updatePassword'])  ->name('customer.password.update')->middleware('throttle:6,1');
    Route::get('/customer/account/deletion-status', [CustomerDashboard::class, 'accountDeletionStatus'])->name('customer.account.status');
    Route::delete('/customer/account',             [CustomerDashboard::class, 'destroyAccount'])      ->name('customer.account.destroy');
});

// ── Guest Booking (public — no auth required) ──────────────────────────────
    Route::post('/booking/guest', [GuestBookingController::class, 'store'])->middleware('maintenance')->name('booking.guest');

// ── Availability picker API (public — counts only, no customer data) ──────
    Route::get('/booking/availability', \App\Http\Controllers\AvailabilityController::class)->name('booking.availability');


// ── Scheduler trigger (token-guarded, called by an external cron) ─────────
// Render's free tier has no cron jobs, no worker and no shell, so nothing
// scheduled ever ticked in production. An outside cron service pings this on a
// fixed interval and Laravel decides what is actually due. Deliberately
// outside the 'maintenance' middleware: reminders should keep going out while
// the shop has the customer-facing site switched off.
Route::get('/cron/{task?}', \App\Http\Controllers\CronController::class)
    ->middleware('throttle:cron')
    ->name('cron.run');

require __DIR__.'/auth.php';
