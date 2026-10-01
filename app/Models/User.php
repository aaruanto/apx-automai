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
            'password' => 'hashed',
        ];
    }
   /**
    * Where this user belongs after signing in or verifying their email.
    *
    * Breeze ships redirects to a single route('dashboard'), but this app has a
    * dashboard per role and no route by that name — those redirects threw a
    * RouteNotFoundException (500) on email verification and password confirmation.
    */
   public function dashboardRoute(): string
   {
       return match ($this->role) {
           'admin' => 'admin.dashboard',
           'staff' => 'staff.dashboard',
           default => 'customer.dashboard',
       };
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
