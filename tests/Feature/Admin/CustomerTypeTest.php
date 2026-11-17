<?php

use App\Enums\CustomerType;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

/**
 * How the shop came to have each customer on file.
 *
 * Three values, and what each is worth is the point: a Guest has never proved
 * they own the address, a Walk-in is as good as whoever typed it, and a
 * Verified customer set their own password.
 *
 * Verified is NOT email-verified. Nothing in this application writes
 * users.email_verified_at — the comment on the is_guest migration records that
 * assuming otherwise already let registration overwrite a real user's
 * password — so it is defined as is_guest = false.
 */

function ctAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function ctService(): Service
{
    return Service::create([
        'name' => 'CT Svc '.uniqid(), 'price' => 1500, 'duration' => 30,
    ]);
}

// ── The rule, in one place ──────────────────────────────────────────────

it('reads a guest account as Guest', function () {
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => true]);

    expect(CustomerType::forUser($user))->toBe(CustomerType::Guest);
});

it('reads a guest account as Guest even when the shop created the row', function () {
    // The shop may have made the record, but the person still has no password
    // and the booking still came in through the guest form.
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => true]);

    expect(CustomerType::forUser($user, createdByShop: true))->toBe(CustomerType::Guest);
});

it('reads a registered account as Verified', function () {
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => false]);

    expect(CustomerType::forUser($user))->toBe(CustomerType::Verified);
});

it('does not mean email-verified by Verified', function () {
    // The thing most likely to be assumed, and the assumption that already
    // caused a real bug once. email_verified_at is never written by anything
    // in this app, so it cannot be what the word means.
    $unverified = User::factory()->create([
        'role' => 'customer', 'is_guest' => false, 'email_verified_at' => null,
    ]);

    expect($unverified->email_verified_at)->toBeNull();
    expect(CustomerType::forUser($unverified))->toBe(CustomerType::Verified);
});

it('reads a placeholder address as Walk-in on its own', function () {
    // Admin\BookingController synthesises these for a walk-in who gave no
    // email, and it is the only thing in the application that writes that
    // domain — so a row carrying one was typed in at the counter, whatever
    // else is known about it.
    $user = User::factory()->create([
        'role' => 'customer', 'is_guest' => false,
        'email' => 'juandelacruz@apxautomai.local',
    ]);

    expect(CustomerType::forUser($user))->toBe(CustomerType::WalkIn);
});

// ── Every path that creates a customer ──────────────────────────────────

it('makes a registered customer Verified, and gives them a row at all', function () {
    // Registration used to create a users row and nothing else, so a customer
    // who signed up and never signed in was absent from the admin Customer
    // tab entirely — that screen lists customers rows.
    $this->post('/register', [
        'first_name' => 'Juan', 'last_name' => 'dela Cruz',
        'email' => 'juan.register@example.com',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ])->assertRedirect();

    $user = User::where('email', 'juan.register@example.com')->sole();

    expect($user->customer)->not->toBeNull();
    expect($user->customer->customer_type)->toBe(CustomerType::Verified);
});

it('makes a guest booking Guest, and gives them a row at all', function () {
    $this->postJson('/booking/guest', [
        'guest_name' => 'Walk In', 'guest_email' => 'ct-guest@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'CTG 1111', 'vehicle_type' => 'car',
        'service_ids' => [ctService()->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertOk();

    $user = User::where('email', 'ct-guest@example.com')->sole();

    expect($user->is_guest)->toBeTrue();
    expect($user->customer)->not->toBeNull();
    expect($user->customer->customer_type)->toBe(CustomerType::Guest);
});

it('makes a customer the shop adds a Walk-in', function () {
    $this->actingAs(ctAdmin())->post('/admin/customers', [
        'name' => 'Counter Customer', 'email' => 'counter@example.com',
        'phone' => '09171234567', 'plate' => 'CTW 1111',
        'car_make' => 'Toyota', 'car_model' => 'Vios', 'car_year' => 2021,
    ])->assertRedirect();

    $user = User::where('email', 'counter@example.com')->sole();

    expect($user->customer->customer_type)->toBe(CustomerType::WalkIn);
});

it('makes a walk-in the shop books a Walk-in, and gives them a row at all', function () {
    // The other admin path. It created a users row and stopped, which is half
    // the reason Walk-in was a type almost nothing could display.
    $this->actingAs(ctAdmin())->post('/admin/bookings', [
        'customer_name' => 'Booked At Counter', 'customer_phone' => '09177654321',
        'plate' => 'CTW 2222', 'car_make' => 'Toyota', 'car_model' => 'Vios',
        'car_year' => 2021, 'car_type' => 'car',
        'service_ids' => [ctService()->id],
        'booking_date' => nextWeekday(), 'booking_time' => '11:00',
    ])->assertRedirect();

    $user = User::where('phone', '09177654321')->sole();

    expect($user->customer)->not->toBeNull();
    expect($user->customer->customer_type)->toBe(CustomerType::WalkIn);
});

it('does not classify a registered customer as a guest for signing in first', function () {
    // The customer dashboard creates the row lazily if nothing else has. It
    // used to default the type, which would have made every customer whose
    // row was born there a Guest.
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => false]);

    expect(Customer::where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)->get('/customer/dashboard')->assertOk();

    expect($user->customer->refresh()->customer_type)->toBe(CustomerType::Verified);
});

// ── Claiming an account ─────────────────────────────────────────────────

it('turns a guest into Verified when they set a password', function () {
    // Following the link in their inbox proves they own the address and gives
    // them credentials they chose, which is exactly what Verified means — so
    // leaving the row saying Guest would have the Customer tab contradicting
    // the thing that just happened.
    $user = User::factory()->create([
        'role' => 'customer', 'is_guest' => true, 'email' => 'claimer@example.com',
    ]);

    Customer::create([
        'user_id' => $user->id, 'customer_type' => CustomerType::Guest,
    ]);

    $this->post('/reset-password', [
        'token' => Password::createToken($user),
        'email' => 'claimer@example.com',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->is_guest)->toBeFalse();
    expect($user->customer->refresh()->customer_type)->toBe(CustomerType::Verified);
});

it('leaves an ordinary password reset alone', function () {
    // Only on the guest transition. A Walk-in resetting their password has
    // still never been through registration, and the shop's classification of
    // how the record arrived does not change because they picked a new one.
    $user = User::factory()->create([
        'role' => 'customer', 'is_guest' => false, 'email' => 'resetter@example.com',
    ]);

    Customer::create([
        'user_id' => $user->id, 'customer_type' => CustomerType::WalkIn,
    ]);

    $this->post('/reset-password', [
        'token' => Password::createToken($user),
        'email' => 'resetter@example.com',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ])->assertSessionHasNoErrors();

    expect($user->customer->refresh()->customer_type)->toBe(CustomerType::WalkIn);
});

it('does not undo a claim when that customer books as a guest again', function () {
    // The guest form uses firstOrCreate and deliberately does not rewrite the
    // type: a customer who has claimed their account is Verified, and coming
    // back through the public form must not demote them.
    $user = User::factory()->create([
        'role' => 'customer', 'is_guest' => false, 'email' => 'returning@example.com',
    ]);

    Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    // A registered account is turned away from the guest form, which is the
    // existing rule — so the type cannot be touched by it at all.
    $this->postJson('/booking/guest', [
        'guest_name' => 'Returning', 'guest_email' => 'returning@example.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'CTR 1111', 'vehicle_type' => 'car',
        'service_ids' => [ctService()->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ])->assertStatus(422);

    expect($user->customer->refresh()->customer_type)->toBe(CustomerType::Verified);
});

// ── The admin Customer tab ──────────────────────────────────────────────

it('shows the type as a badge on the customer list', function () {
    $guest = User::factory()->create(['role' => 'customer', 'name' => 'Guesty', 'is_guest' => true]);
    Customer::create(['user_id' => $guest->id, 'customer_type' => CustomerType::Guest]);

    $html = $this->actingAs(ctAdmin())->get('/admin/customers')->assertOk()->getContent();

    expect($html)->toContain('badge '.CustomerType::Guest->badgeClass());
    expect($html)->toContain('>Guest<');
});

it('offers a filter for every type, beside the search box it already had', function () {
    $html = $this->actingAs(ctAdmin())->get('/admin/customers')->assertOk()->getContent();

    expect($html)->toContain('id="filterType"');

    foreach (CustomerType::cases() as $case) {
        expect($html)->toContain('<option value="'.$case->value.'">'.$case->label().'</option>');
    }
});

it('filters in the browser, matching the pattern this list already uses', function () {
    // The list loads everything and filters client-side. A server-side filter
    // for one column would mean half the controls on that bar reloading the
    // page and half not.
    $user = User::factory()->create(['role' => 'customer', 'is_guest' => true]);
    Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Guest]);

    $html = $this->actingAs(ctAdmin())->get('/admin/customers')->assertOk()->getContent();

    expect($html)->toContain('data-type="guest"');
    expect($html)->toContain("row.dataset.type === type");
});

it('lets the shop correct a type on the edit form', function () {
    // The one field the backfill could get wrong: a customer added at the
    // counter WITH a real email address is indistinguishable from one who
    // registered, so those read Verified and this is where they are put right.
    $user     = User::factory()->create(['role' => 'customer', 'name' => 'Mis Filed']);
    $customer = Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    $this->actingAs(ctAdmin())
        ->put("/admin/customers/{$customer->id}", ['customer_type' => CustomerType::WalkIn->value])
        ->assertRedirect(route('admin.customers.index'))
        ->assertSessionHas('success');

    expect($customer->refresh()->customer_type)->toBe(CustomerType::WalkIn);
});

it('records who changed a type and what it was', function () {
    $user     = User::factory()->create(['role' => 'customer', 'name' => 'Audited Customer']);
    $customer = Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    $this->actingAs(ctAdmin())
        ->put("/admin/customers/{$customer->id}", ['customer_type' => CustomerType::Guest->value]);

    $entry = AuditLog::where('action', 'customer.type_changed')->latest('id')->first();

    expect($entry)->not->toBeNull();
    expect($entry->old_values)->toMatchArray(['customer_type' => 'verified']);
    expect($entry->new_values)->toMatchArray(['customer_type' => 'guest']);
    // And who did it, which is the point of recording it at all.
    expect($entry->user_id)->not->toBeNull();
});

it('still refuses everything else on that form', function () {
    // Name, email, phone and address belong to the customer. The type is the
    // shop's classification of the record, which is a different kind of thing.
    $user     = User::factory()->create(['role' => 'customer', 'name' => 'Original Name']);
    $customer = Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    $this->actingAs(ctAdmin())->put("/admin/customers/{$customer->id}", [
        'name'          => 'Changed By Admin',
        'email'         => 'changed@example.com',
        'phone'         => '09990000000',
        'customer_type' => CustomerType::WalkIn->value,
    ])->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Original Name');
    expect($user->email)->not->toBe('changed@example.com');
    // And the one field it does accept went through.
    expect($customer->refresh()->customer_type)->toBe(CustomerType::WalkIn);
});

it('says nothing changed rather than claiming a save', function () {
    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    $this->actingAs(ctAdmin())
        ->put("/admin/customers/{$customer->id}", ['customer_type' => CustomerType::Verified->value])
        ->assertSessionHas('error');
});

it('refuses a type the shop invented', function () {
    $user     = User::factory()->create(['role' => 'customer']);
    $customer = Customer::create(['user_id' => $user->id, 'customer_type' => CustomerType::Verified]);

    $this->actingAs(ctAdmin())
        ->put("/admin/customers/{$customer->id}", ['customer_type' => 'vip'])
        ->assertSessionHasErrors('customer_type');

    expect($customer->refresh()->customer_type)->toBe(CustomerType::Verified);
});

it('is not the loyalty tier column coming back', function () {
    // customers.tier was dropped by 2026_08_20_000000_drop_loyalty_feature and
    // there are no tiers in this system. Three values about provenance, and
    // none of them is a rank.
    expect(\Illuminate\Support\Facades\Schema::hasColumn('customers', 'tier'))->toBeFalse();
    expect(CustomerType::values())->toBe(['guest', 'walk_in', 'verified']);
});

// ── The backfill ────────────────────────────────────────────────────────

it('defaults to the least-trusted thing a row could be', function () {
    // A row nobody has classified must not present as a verified account.
    $user = User::factory()->create(['role' => 'customer']);

    $id = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
        'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(Customer::find($id)->customer_type)->toBe(CustomerType::Guest);
});

it('gives every live customer account a row to appear on', function () {
    // What the migration does for existing data, asserted through the paths
    // that would have left a user without one.
    $booking = Booking::create([
        'user_id'    => ($u = User::factory()->create(['role' => 'customer']))->id,
        'vehicle_id' => Vehicle::create([
            'user_id' => $u->id, 'make' => 'Toyota', 'model' => 'Vios',
            'year' => 2021, 'plate_number' => 'CTB 1111',
        ])->id,
        'service_id'       => ctService()->id,
        'booking_date'     => nextWeekday(), 'booking_time' => '09:00',
        'status'           => \App\Enums\BookingStatus::PendingReview,
        'reference_number' => 'CTB-0001',
    ]);

    // This user has no customers row — the fixture made one the old way.
    expect(Customer::where('user_id', $u->id)->exists())->toBeFalse();

    // Opening their dashboard is the last remaining path that creates one,
    // and it classifies rather than defaulting.
    $this->actingAs($u)->get('/customer/dashboard')->assertOk();

    expect(Customer::where('user_id', $u->id)->sole()->customer_type)
        ->toBe(CustomerType::Verified);

    expect($booking->refresh()->user_id)->toBe($u->id);
});
