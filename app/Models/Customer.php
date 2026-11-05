<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vehicle;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        // dob is no longer shown or written on the customer profile — the shop
        // has no use for a birthday. The column and anything already stored in
        // it stay put, and AccountAnonymizer still nulls it on account
        // deletion, which needs it mass-assignable.
        'dob',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    public function vehicle()
    {
        return $this->hasOneThrough(
            Vehicle::class,
            User::class,
            'id',      // users.id
            'user_id', // vehicles.user_id
            'user_id', // customers.user_id
            'id'       // users.id
        );
    }
}