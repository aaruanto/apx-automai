<?php
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboard;
// Public routes
Route::get('/', fn() => view('index'));
Route::get('/about', fn() => view('about'));
Route::get('/booking', fn() => view('booking'));
Route::get('/contact', fn() => view('contact'));
Route::get('/service', fn() => view('service'));
Route::get('/team', fn() => view('team'));
Route::get('/testimonial', fn() => view('testimonial'));

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin only
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings',                  [BookingController::class, 'index'])   ->name('bookings.index');
    Route::get('/bookings/create',           [BookingController::class, 'create'])  ->name('bookings.create');
    Route::post('/bookings',                 [BookingController::class, 'store'])   ->name('bookings.store');
    Route::get('/bookings/schedule',         [BookingController::class, 'schedule'])->name('bookings.schedule');
    Route::get('/bookings/cancelled',        [BookingController::class, 'cancelled'])->name('bookings.cancelled');
    Route::get('/bookings/{id}/edit',        [BookingController::class, 'edit'])    ->name('bookings.edit');
    Route::put('/bookings/{id}',             [BookingController::class, 'update'])  ->name('bookings.update');
    Route::get('/bookings/{id}/rebook',      [BookingController::class, 'rebook'])  ->name('bookings.rebook');
});

// Staff only
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', fn() => view('dashboard.admin-dashboard'))->name('staff.dashboard');
});

// Customer only
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboard::class, 'index'])->name('customer.dashboard');
    Route::post('/customer/bookings', [CustomerDashboard::class, 'store'])->name('customer.bookings.store');
   Route::post('/customer/vehicles', [CustomerDashboard::class, 'storeVehicle'])->name('customer.vehicles.store');
   Route::delete('/customer/vehicles/{id}', [CustomerDashboard::class, 'destroyVehicle'])->name('customer.vehicles.destroy');
});

require __DIR__.'/auth.php';