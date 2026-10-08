<?php

namespace App\Services;

use App\Models\AccountDeletion;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Two-stage self-service account deletion.
 *
 *   Stage 1 — deactivate()  : soft delete. The account disappears and can't be
 *                             logged into, but every field is left untouched, so
 *                             logging in during the grace period restores it.
 *   Stage 2 — anonymize()   : runs once the grace period expires (see the
 *                             accounts:purge-expired command). Personal data is
 *                             overwritten. Irreversible.
 *
 * Bookings are never deleted at either stage. bookings.user_id and
 * vehicles.user_id are both onDelete('cascade'), so a real delete would take the
 * customer's booking history with it — and since admin revenue reports are
 * derived by joining bookings to services, past revenue would silently drop off
 * the books.
 */
class AccountAnonymizer
{
    /** Days an account stays recoverable before its data is erased. */
    public const GRACE_DAYS = 30;

    /** The shop is physically holding the customer's vehicle. */
    public const IN_PROGRESS_STATUSES = ['in_progress'];

    /** Reserved future slots. */
    public const UPCOMING_STATUSES = ['pending', 'confirmed'];

    public function inProgressBookings(User $user)
    {
        return Booking::where('user_id', $user->id)
            ->whereIn('status', self::IN_PROGRESS_STATUSES)
            ->get();
    }

    public function upcomingBookings(User $user)
    {
        return Booking::where('user_id', $user->id)
            ->whereIn('status', self::UPCOMING_STATUSES)
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->get();
    }

    /**
     * Stage 1. Deactivates the account without touching any personal data.
     * Caller is responsible for confirming the password and logging out.
     */
    public function deactivate(User $user): AccountDeletion
    {
        return DB::transaction(function () use ($user) {
            $cancelled = 0;
            foreach ($this->upcomingBookings($user) as $booking) {
                // Free the slot straight away — the shop shouldn't hold a bay for
                // an account that's on its way out.
                $booking->cancel('Account deleted by customer', $user->id);
                $cancelled++;
            }

            $user->forceFill(['is_active' => false])->save();
            $user->delete(); // soft delete: row kept, login blocked by the global scope

            return AccountDeletion::create([
                'user_id'             => $user->id,
                'purge_at'            => now()->addDays(self::GRACE_DAYS),
                'bookings_cancelled'  => $cancelled,
                'bookings_retained'   => Booking::where('user_id', $user->id)->count(),
                'vehicles_anonymized' => 0,
            ]);
        });
    }

    /** Is this soft-deleted account still inside its grace period? */
    public function isRestorable(User $user): bool
    {
        $record = AccountDeletion::where('user_id', $user->id)
            ->whereNull('anonymized_at')
            ->latest()
            ->first();

        return $record && $record->purge_at && $record->purge_at->isFuture();
    }

    /** Stage 1 undo — used when someone logs in during the grace period. */
    public function restore(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->restore();
            $user->forceFill(['is_active' => true])->save();

            AccountDeletion::where('user_id', $user->id)
                ->whereNull('anonymized_at')
                ->update(['restored_at' => now()]);
        });
    }

    /**
     * Stage 2. Overwrites the personal data. Not reversible.
     */
    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            $vehicles = Vehicle::withTrashed()->where('user_id', $user->id)->get();
            foreach ($vehicles as $vehicle) {
                // Make/model/year stay — they're service history and identify
                // nobody. The plate does, and the column is unique and not
                // nullable, so it gets a unique placeholder.
                $vehicle->update([
                    'plate_number' => 'REMOVED-' . $vehicle->id,
                    'color'        => null,
                ]);
            }

            if ($user->customer) {
                $user->customer->update(['phone' => null, 'dob' => null, 'address' => null]);
            }

            $user->forceFill([
                'name'              => 'Deleted Customer',
                'email'             => 'deleted-' . $user->id . '@removed.invalid',
                'phone'             => null,
                'password'          => bcrypt(bin2hex(random_bytes(16))),
                'remember_token'    => null,
                'email_verified_at' => null,
                'is_active'         => false,
            ])->save();

            AccountDeletion::where('user_id', $user->id)
                ->whereNull('anonymized_at')
                ->update([
                    'anonymized_at'       => now(),
                    'vehicles_anonymized' => $vehicles->count(),
                ]);
        });
    }

    /** Accounts whose grace period has run out. */
    public function pendingPurge()
    {
        return AccountDeletion::whereNull('anonymized_at')
            ->whereNull('restored_at')
            ->where('purge_at', '<=', now())
            ->get();
    }
}
