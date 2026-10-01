<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Record that an account was deleted. Holds no personal data by design —
 * see the migration for why.
 */
class AccountDeletion extends Model
{
    protected $fillable = [
        'user_id', 'purge_at', 'anonymized_at', 'restored_at',
        'bookings_cancelled', 'bookings_retained', 'vehicles_anonymized',
    ];

    protected $casts = [
        'purge_at'      => 'datetime',
        'anonymized_at' => 'datetime',
        'restored_at'   => 'datetime',
    ];
}
