<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', fn() => view('dashboard.admin-dashboard'))->name('admin.dashboard');
    Route::get('/staff/dashboard', fn() => view('dashboard.admin-dashboard'))->name('staff.dashboard');
    Route::get('/customer/dashboard', fn() => view('dashboard.customer-dashboard'))->name('customer.dashboard');
});
require __DIR__.'/auth.php';
