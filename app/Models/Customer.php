<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vehicle;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'loyalty_points',
        'tier',
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

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }
}