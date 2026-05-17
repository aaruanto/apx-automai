<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'make', 'model', 'plate_number', 'year', 'color', 'is_primary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}