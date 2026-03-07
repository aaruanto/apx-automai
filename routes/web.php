<?php

use Illuminate\Support\Facades\Route;


Route::get('/', fn() => view('index'));
Route::get('/about', fn() => view('about'));
Route::get('/booking', fn() => view('booking'));
Route::get('/contact', fn() => view('contact'));
Route::get('/service', fn() => view('service'));
Route::get('/team', fn() => view('team'));
Route::get('/testimonial', fn() => view('testimonial'));

