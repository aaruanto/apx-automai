<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
   protected $fillable = [
    'name',
    'email',
    'phone',
    'role',
    'password',
    // Accounts are deactivated rather than deleted. It was missing
    // here, so update() silently dropped it and both callers that needed
    // it had to reach past mass assignment with forceFill.
    'is_active',
    'is_guest',
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active'         => 'boolean',
            'is_guest'          => 'boolean',
            'password' => 'hashed',
        ];
    }
   /**
    * Where this user belongs after signing in or verifying their email.
    *
    * Breeze ships redirects to a single route('dashboard'), but this app has a
    * dashboard per role and no route by that name — those redirects threw a
    * RouteNotFoundException (500) on email verification and password confirmation.
    *
    * There are only two roles. A 'staff' value surviving in an old row falls
    * through to the customer dashboard rather than a route that no longer
    * exists, so a stale account cannot produce a 500.
    */
   public function dashboardRoute(): string
   {
       return $this->role === 'admin' ? 'admin.dashboard' : 'customer.dashboard';
   }
   public function vehicles()
{
    return $this->hasMany(\App\Models\Vehicle::class, 'user_id');
}

public function customer()
{
    return $this->hasOne(\App\Models\Customer::class, 'user_id');
}
}
