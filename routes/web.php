<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => view('dashboard.admin-dashboard'))->name('admin.dashboard');
});

// Staff only
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', fn() => view('dashboard.admin-dashboard'))->name('staff.dashboard');
});

// Customer only
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', fn() => view('dashboard.customer-dashboard'))->name('customer.dashboard');
});

require __DIR__.'/auth.php';
