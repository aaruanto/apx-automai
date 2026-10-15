<?php

namespace App\Models;

use App\Services\SmsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A mechanic is a record, not an account.
 *
 * They are a name and a contact number the shop keeps so work can be assigned
 * and someone can be rung about it. There is deliberately no login: this is a
 * small business with one administrator, and a separate staff portal was
 * overhead with no reader.
 */
class Mechanic extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'contact_number', 'specialty', 'status'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Assignable mechanics: someone who has left should get no new work. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('name');
    }

    /**
     * The contact number as it should be dialled or texted.
     *
     * Stored as entered so the admin sees back what they typed, and normalised
     * on the way out through the same rules the SMS service uses, so the two
     * can never disagree about what a Philippine mobile number is.
     */
    public function normalisedContact(): ?string
    {
        return app(SmsService::class)->normalise($this->contact_number);
    }
}
