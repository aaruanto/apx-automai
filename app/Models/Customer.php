<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vehicle;

class Customer extends Model
{
    protected $casts = [
        // Guest, Walk-in or Verified. App\Enums\CustomerType carries what the
        // three mean, and in particular why Verified is not email-verified.
        'customer_type' => CustomerType::class,
    ];

    protected $fillable = [
        'user_id',
        'customer_type',
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

    /**
     * Give this customer the type their account implies.
     *
     * Asked of the enum rather than decided here, so the five paths that
     * create a customer and the one that claims an account cannot each reach
     * a different answer — which is how customers.tier used to end up
     * disagreeing with the ledger.
     *
     * $createdByShop is the one thing no column records: there is no flag
     * saying an admin typed this customer in, so only the two admin paths
     * pass true.
     */
    public function classify(bool $createdByShop = false): self
    {
        if ($this->user) {
            $this->customer_type = CustomerType::forUser($this->user, $createdByShop);
        }

        return $this;
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