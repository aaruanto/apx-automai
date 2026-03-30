<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BookingController extends Controller
{
    public function index()
    {
        return view('admin.bookings.index');
    }

    public function create()
    {
        return view('admin.bookings.create');
    }

    public function store()
    {
        // TODO: validate + save to DB
    }

    public function edit($id)
    {
        return view('admin.bookings.create');
    }

    public function update($id)
    {
        // TODO: validate + update in DB
    }

    public function schedule()
    {
        return view('admin.bookings.schedule');
    }

    public function cancelled()
    {
        return view('admin.bookings.cancelled');
    }

    public function rebook($id)
    {
        // TODO: clone cancelled booking as new pending booking
    }
}