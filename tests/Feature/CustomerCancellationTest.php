<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * A customer cancelling their own booking.
 *
 * Booking::cancel() writes four fields and deliberately nothing else; the
 * consequences lived inline in the admin controller, so every other way of
 * cancelling had to remember to copy them. Three of the four did not.
 *
 * The customer dashboard was the worst of them, and it cost real money in
 * both directions: points earned for work that will now not happen stayed on
 * the balance, and points the customer had redeemed against the booking were
 * simply gone. Cancel a booking you spent points on and you were out of
 * pocket, with no audit row and no email to show for it.
 *
 * BookingCancellation now owns all of it and every path calls the one method.
 * These tests pin the customer path to the admin one: for the same booking,
 * the same things must happen.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

function ccMail(): \Illuminate\Support\Collection
{
    return collect(Mail::getSymfonyTransport()->messages());
}

function ccCustomer(): User
{
    static $n = 1;

    return User::factory()->create([
        'role' => 'customer', 'is_active' => true, 'is_guest' => false,
        'email' => 'cc.customer'.$n++.'@gmail.com',
    ]);
}

function ccBooking(User $user, string $status = 'confirmed'): Booking
{
    static $n = 1;

    $vehicle = Vehicle::create([
        'user_id' => $user->id, 'make' => 'Toyota', 'model' => 'Vios', 'year' => 2021,
        'plate_number' => 'CCX 000'.$n, 'vehicle_type' => 'car',
    ]);

    $service = Service::create(['name' => 'Oil Change '.$n, 'price' => 2000, 'duration' => 60]);

    $booking = Booking::create([
        'user_id' => $user->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekdayAfter(4), 'booking_time' => '10:00', 'duration' => 60,
        'status' => $status, 'reference_number' => 'APX-CC-'.$n++,
    ]);

    $booking->services()->sync([$service->id => ['price' => 2000, 'duration' => 60]]);

    return $booking->fresh();
}

/** Points earned on a completed booking, so there is something to reverse. */
function ccEarnPoints(Booking $booking): int
{
    $booking->update(['status' => 'completed']);
    app(LoyaltyService::class)->earnFor($booking->fresh());
    $booking->update(['status' => 'confirmed']);

    return app(LoyaltyService::class)->balanceFor($booking->user);
}

// ── The money ─────────────────────────────────────────────────────────────

it('takes back points earned for work that will not happen', function () {
    $user    = ccCustomer();
    $booking = ccBooking($user);

    $earned = ccEarnPoints($booking);
    expect($earned)->toBeGreaterThan(0);

    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', [
        'reason' => 'Something came up',
    ])->assertOk();

    expect(app(LoyaltyService::class)->balanceFor($user->fresh()))->toBe(0);
});

it('returns points the customer spent on the booking', function () {
    // The half that left them out of pocket. Redeem against a booking, cancel
    // it, and the points used to stay spent.
    $user    = ccCustomer();
    $earning = ccBooking($user);
    ccEarnPoints($earning);

    $balanceBefore = app(LoyaltyService::class)->balanceFor($user->fresh());
    expect($balanceBefore)->toBeGreaterThan(0);

    $booking = ccBooking($user);
    app(LoyaltyService::class)->redeemFor($booking, $user->fresh(), $balanceBefore);

    expect(app(LoyaltyService::class)->balanceFor($user->fresh()))->toBe(0);

    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', [
        'reason' => 'Changed my mind',
    ])->assertOk();

    expect(app(LoyaltyService::class)->balanceFor($user->fresh()))->toBe($balanceBefore);
});

it('leaves the same balance whether the customer or the admin cancels', function () {
    // The point of the shared service: the only difference between the two
    // paths should be who did it.
    $makeAndEarn = function (): array {
        $user    = ccCustomer();
        $booking = ccBooking($user);
        ccEarnPoints($booking);

        return [$user, $booking];
    };

    [$selfUser, $selfBooking] = $makeAndEarn();
    test()->actingAs($selfUser)->patchJson('/customer/bookings/'.$selfBooking->id.'/cancel', [
        'reason' => 'Something came up',
    ])->assertOk();

    [$adminUser, $adminBooking] = $makeAndEarn();
    $admin = User::factory()->create(['role' => 'admin']);
    test()->actingAs($admin)->patchJson('/admin/bookings/'.$adminBooking->id.'/cancel', [
        'reason' => 'Shop closed that day',
    ])->assertOk();

    $loyalty = app(LoyaltyService::class);

    expect($loyalty->balanceFor($selfUser->fresh()))
        ->toBe($loyalty->balanceFor($adminUser->fresh()));
});

it('does not refund twice when the cancel is submitted twice', function () {
    // reverseFor and refundSpentFor are idempotent on a unique
    // (booking_id, reason); this proves the controller does not find a way
    // around that.
    $user    = ccCustomer();
    $earning = ccBooking($user);
    ccEarnPoints($earning);

    $balanceBefore = app(LoyaltyService::class)->balanceFor($user->fresh());

    $booking = ccBooking($user);
    app(LoyaltyService::class)->redeemFor($booking, $user->fresh(), $balanceBefore);

    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', ['reason' => 'One'])
        ->assertOk();

    // The second finds nothing cancellable, so it must change nothing.
    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', ['reason' => 'Two'])
        ->assertStatus(404);

    expect(app(LoyaltyService::class)->balanceFor($user->fresh()))->toBe($balanceBefore);
});

// ── The record and the notice ─────────────────────────────────────────────

it('writes an audit row when a customer cancels', function () {
    $user    = ccCustomer();
    $booking = ccBooking($user);

    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', [
        'reason' => 'Something came up',
    ])->assertOk();

    $log = AuditLog::where('action', 'booking.cancelled')->sole();

    // record()'s $label argument is stored in the `subject` column.
    expect($log->subject)->toBe($booking->reference_number)
        ->and($log->reason)->toBe('Something came up')
        ->and($log->auditable_id)->toBe($booking->id)
        // The customer is the actor here, not the shop.
        ->and($log->user_id)->toBe($user->id);
});

it('sends the customer exactly one email', function () {
    $user    = ccCustomer();
    $booking = ccBooking($user);

    $this->actingAs($user)->patchJson('/customer/bookings/'.$booking->id.'/cancel', [
        'reason' => 'Something came up',
    ])->assertOk();

    expect(ccMail())->toHaveCount(1)
        ->and($user->fresh()->notifications->pluck('data.event')->all())
        ->toBe([BookingNotification::BOOKING_CANCELLED]);
});

it('reaches a guest account, whose address is real even though they never signed in', function () {
    $guest   = ccCustomer();
    $guest->update(['is_guest' => true]);
    $booking = ccBooking($guest);

    $this->actingAs($guest)->patchJson('/customer/bookings/'.$booking->id.'/cancel', [
        'reason' => 'Cannot make it',
    ])->assertOk();

    expect(ccMail())->toHaveCount(1)
        ->and(ccMail()->first()->getOriginalMessage()->getTo()[0]->getAddress())
        ->toBe($guest->email);
});

// ── The scope still holds ─────────────────────────────────────────────────

it('will not let a customer cancel somebody elses booking', function () {
    $owner     = ccCustomer();
    $booking   = ccBooking($owner);
    $outsider  = ccCustomer();

    $this->actingAs($outsider)
        ->patchJson('/customer/bookings/'.$booking->id.'/cancel', ['reason' => 'Not mine'])
        ->assertStatus(404);

    expect($booking->fresh()->status)->toBe('confirmed')
        ->and(ccMail())->toHaveCount(0);
});

// ── The no-show sweep shares the same consequences ────────────────────────

it('reverses points and tells the customer when the no-show sweep cancels', function () {
    // It used to write the columns with a raw update(), going around both the
    // model's rules and everything that follows from a cancellation.
    $user    = ccCustomer();
    $earning = ccBooking($user);
    ccEarnPoints($earning);

    $balanceBefore = app(LoyaltyService::class)->balanceFor($user->fresh());

    $booking = ccBooking($user);
    app(LoyaltyService::class)->redeemFor($booking, $user->fresh(), $balanceBefore);

    // Put it far enough in the past that the grace period has elapsed.
    $booking->update([
        'booking_date' => now()->subDay()->toDateString(),
        'booking_time' => '09:00',
    ]);

    $this->artisan('bookings:cancel-no-shows')->assertSuccessful();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and(app(LoyaltyService::class)->balanceFor($user->fresh()))->toBe($balanceBefore)
        ->and(AuditLog::where('action', 'booking.cancelled')->count())->toBe(1)
        ->and(ccMail())->toHaveCount(1);
});
