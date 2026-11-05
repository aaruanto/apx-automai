<?php

/**
 * The reference number is a ticket, so it is disclosed once — on confirmation.
 *
 * A booking is created 'pending' and the shop has not agreed to the slot yet,
 * but every surface on the create path printed the reference anyway: the
 * acknowledgment email put it in the subject and twice in the body under
 * "Please bring this reference number when you arrive", the guest success
 * screen printed it in a badge, the dashboard's success modal printed it, and
 * the dashboard's own list labelled pending rows '#BK-0001'. Customers turned
 * up on the strength of it, for appointments nobody had approved.
 *
 * The number is still generated at creation — BookingAvailability::reserve()
 * assigns it inside the transaction, and every admin screen, the eight audit
 * labels and the slot-clash message read it, because a pending booking is
 * exactly the one the shop needs to be able to name. Withholding it is a rule
 * about customer-facing output, which means it has to be kept at each site
 * rather than enforced by the schema. These tests are that enforcement.
 *
 * Mail::fake() is deliberately not used. MailFake::sendMail returns early for
 * anything that is not a Mailable, so assertSentCount reads zero however much
 * mail went out; the array transport is the only honest counter. See CLAUDE.md.
 */

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('mail.default', 'array');
    Mail::purge('array');
});

/** Every message actually handed to the transport. */
function refSent(): array
{
    return Mail::getSymfonyTransport()->messages()->all();
}

function refSubjects(): array
{
    return array_map(fn ($m) => $m->getOriginalMessage()->getSubject(), refSent());
}

/**
 * Subject and body of one sent message, as one searchable string.
 *
 * The bodies are read with getHtmlBody()/getTextBody() rather than
 * toString(): the serialised form is quoted-printable, which inserts soft
 * line breaks at 76 characters and can split a reference number in half —
 * so a "does not contain" assertion would pass on a message that plainly
 * does contain it.
 */
function refWhole(int $i = 0): string
{
    $email = refSent()[$i]->getOriginalMessage();

    return $email->getSubject()."\n".($email->getHtmlBody() ?? '')."\n".($email->getTextBody() ?? '');
}

/**
 * A booking that already exists, with a reference distinctive enough that
 * assertDontSee cannot match it by accident.
 *
 * Prior state is CREATED rather than booked through a first request, because
 * dispatch(...)->afterResponse() registers an application terminating
 * callback that Application::terminate() never clears: a second request in
 * the same test re-runs the first request's closure, and the mailbox then
 * shows the first booking's acknowledgment twice. See CLAUDE.md.
 */
function refBooking(string $status = 'pending', ?User $customer = null): Booking
{
    $unique   = substr(str_replace('.', '', uniqid('', true)), -6);
    $customer = $customer ?: User::factory()->create([
        'role'  => 'customer',
        'email' => 'ref'.$unique.'@gmail.com',
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'REF '.$unique, 'color' => 'Red',
    ]);

    $service = Service::create([
        'name' => 'Disclosure Service '.$unique, 'price' => 1500, 'duration' => 45,
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'service_id' => $service->id,
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
        'status' => $status, 'reference_number' => 'ZREF-'.$unique,
    ]);

    $booking->services()->attach($service->id, ['price' => 1500, 'duration' => 45]);

    return $booking->fresh(['user', 'service', 'vehicle', 'services']);
}

function refService(): Service
{
    return Service::create([
        'name' => 'Disclosure Svc '.substr(str_replace('.', '', uniqid('', true)), -6),
        'price' => 1500, 'duration' => 60,
    ]);
}

function refGuestPayload(array $overrides = []): array
{
    $unique = substr(str_replace('.', '', uniqid('', true)), -4);

    return array_merge([
        'guest_name' => 'Juan Dela Cruz', 'guest_email' => 'guest'.$unique.'@gmail.com',
        'guest_phone' => '09171234567',
        'vehicle_make' => 'Toyota', 'vehicle_model' => 'Vios', 'vehicle_year' => 2021,
        'vehicle_plate' => 'DSC '.$unique, 'vehicle_type' => 'car',
        'service_ids' => [refService()->id],
        'booking_date' => nextWeekday(), 'booking_time' => '10:00',
    ], $overrides);
}

// ── the acknowledgment: a receipt, not a ticket ──────────────────────────

it('sends a guest an acknowledgment whose subject carries no reference', function () {
    $this->postJson('/booking/guest', refGuestPayload())->assertOk();

    $booking = Booking::sole();
    expect($booking->status)->toBe('pending');

    // Two messages, not one: a brand-new guest also gets GuestAccountCreated
    // with their setup link. So this asserts the acknowledgment is among them
    // and that NONE of them names the reference — which is the stronger
    // claim anyway.
    $subjects = refSubjects();

    expect(collect($subjects)->filter(fn ($s) => str_contains($s, 'We have your booking request')))
        ->toHaveCount(1);

    foreach ($subjects as $subject) {
        expect($subject)->not->toContain($booking->reference_number);
    }
});

it('sends a guest an acknowledgment whose body carries no reference', function () {
    $this->postJson('/booking/guest', refGuestPayload())->assertOk();

    $booking = Booking::sole();

    // Every message this path sent, not just the first: the acknowledgment
    // and the guest's account-setup link both land in the same inbox.
    foreach (array_keys(refSent()) as $i) {
        $whole = strtolower(refWhole($i));

        expect($whole)->not->toContain(strtolower($booking->reference_number));

        // Nor anything instructing the customer to bring or quote a number.
        expect($whole)->not->toContain('bring this reference');
        expect($whole)->not->toContain('reference no');
    }
});

it('keeps what the acknowledgment is actually for', function () {
    // Stripping the ticket must not strip the receipt: the customer still
    // needs to know what they asked for and that it is not settled yet.
    $service = refService();

    $this->postJson('/booking/guest', refGuestPayload(['service_ids' => [$service->id]]))->assertOk();

    $whole = strtolower(refWhole());

    expect($whole)->toContain(strtolower($service->name));
    expect($whole)->toContain('pending');
    // And that a second email is coming, so "no number" cannot read as
    // "we lost your booking".
    expect($whole)->toContain('confirm');
});

it('renders the acknowledgment without a reference when asked directly', function () {
    // The mailable in isolation, so the rule holds for any future caller and
    // not only the two create paths that exist today.
    $booking = refBooking();

    expect((new BookingReceived($booking))->render())
        ->not->toContain($booking->reference_number);

    expect((new BookingReceived($booking))->envelope()->subject)
        ->not->toContain($booking->reference_number);
});

// ── the confirmation: the ticket ─────────────────────────────────────────

it('discloses the reference when an admin confirms', function () {
    $booking = refBooking();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put('/admin/bookings/'.$booking->id, [
            'service_ids'  => $booking->services->pluck('id')->all(),
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'status'       => 'confirmed',
        ])->assertRedirect();

    expect($booking->fresh()->status)->toBe('confirmed');

    expect(refSent())->toHaveCount(1);
    expect(refSubjects()[0])->toContain($booking->reference_number);
    expect(refWhole())->toContain($booking->reference_number);
});

it('words the confirmation as the ticket to bring', function () {
    $booking  = refBooking('confirmed');
    $rendered = strtolower((new BookingConfirmed($booking))->render());

    expect($rendered)->toContain(strtolower($booking->reference_number));
    expect($rendered)->toContain('bring');
});

// ── the on-screen result ─────────────────────────────────────────────────

it('does not return a reference in the guest booking response', function () {
    $response = $this->postJson('/booking/guest', refGuestPayload())->assertOk();

    $response->assertJsonMissingPath('reference');

    // booking_id stays: it is the list's merge key and means nothing at the
    // counter.
    $response->assertJsonStructure(['success', 'booking_id']);

    expect($response->getContent())->not->toContain(Booking::sole()->reference_number);
});

it('does not return a reference in the customer booking response', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $vehicle  = Vehicle::create([
        'user_id' => $customer->id, 'make' => 'Toyota', 'model' => 'Vios',
        'year' => 2020, 'plate_number' => 'CUS 4321', 'color' => 'Blue',
    ]);

    $response = $this->actingAs($customer)->postJson('/customer/bookings', [
        'service_ids'  => [refService()->id],
        'vehicle_id'   => $vehicle->id,
        'booking_date' => nextWeekday(),
        'booking_time' => '10:00',
    ])->assertOk();

    $response->assertJsonMissingPath('reference');
    expect($response->getContent())->not->toContain(Booking::sole()->reference_number);
});

it('leaves no reference badge on the landing pages success screen', function () {
    // The badge is fixed markup now, so there is nothing for the JS to fill
    // in even if a payload ever regrew the field.
    $this->get('/')->assertOk()
        ->assertSee('Pending review')
        ->assertDontSee('Bring this number when you arrive');
});

// ── the customer dashboard ───────────────────────────────────────────────

it('labels a pending booking Pending review on the dashboard', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = refBooking('pending', $customer);

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertDontSee($booking->reference_number)
        ->assertSee('Pending review');
});

it('shows the reference on the dashboard once it is confirmed', function () {
    // A separate test, not a second assertion above: actingAs persists for a
    // whole test, and so would the first booking's pending row.
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = refBooking('confirmed', $customer);

    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertSee($booking->reference_number);
});

it('withholds the reference from the dashboard poll while pending', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = refBooking('pending', $customer);

    $this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk()
        ->assertJsonPath('bookings.0.reference', null)
        // Keyed on the id instead, which is stable from creation and is what
        // the page's merge now matches on.
        ->assertJsonPath('bookings.0.id', $booking->id);
});

it('discloses the reference to the dashboard poll once confirmed', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = refBooking('confirmed', $customer);

    $this->actingAs($customer)->getJson('/customer/dashboard/live')->assertOk()
        ->assertJsonPath('bookings.0.reference', $booking->reference_number);
});

it('keeps a pending reference out of the customers loyalty history', function () {
    // Points are redeemed at the moment a booking is created, so this panel
    // said "Redeemed against ZREF-…" while the booking was still pending —
    // the reference again, by a side door.
    $customer = User::factory()->create(['role' => 'customer']);
    $booking  = refBooking('pending', $customer);

    expect($booking->disclosableReference())->toBeNull();

    $row = new \App\Models\LoyaltyLedger([
        'user_id' => $customer->id, 'booking_id' => $booking->id,
        'points' => -100, 'balance_after' => 0,
        'reason' => \App\Models\LoyaltyLedger::REDEEMED,
    ]);
    $row->setRelation('booking', $booking);

    expect($row->describe())->not->toContain($booking->reference_number);
});

// ── the bell entry ───────────────────────────────────────────────────────

it('keeps the reference out of the customers pending bell entry', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->post('/admin/bookings', [
            'customer_name'  => 'Juan dela Cruz',
            'customer_phone' => '09170009001',
            'customer_email' => 'bell-pending@gmail.com',
            'plate'          => 'DSC 1005',
            'service_id'     => refService()->id,
            'booking_date'   => nextWeekday(),
            'booking_time'   => '10:00',
        ])->assertRedirect();

    $booking = Booking::sole();
    $entry   = $booking->user->notifications()->sole()->data;

    expect($entry['body'])->not->toContain($booking->reference_number);

    // Omitted from the payload too, which is what keeps it out of linkFor()'s
    // ?ref= deep link — the only reader of that key.
    expect($entry)->not->toHaveKey('reference');
    expect(BookingNotification::linkFor($entry))
        ->not->toContain($booking->reference_number);
});

// ── the admin side keeps it ──────────────────────────────────────────────

it('still names a pending booking everywhere the shop needs it', function () {
    // The fix is about what the CUSTOMER sees. A pending booking is exactly
    // the one an admin is chasing, so the admin list must still say which
    // booking it is.
    $booking = refBooking();

    expect($booking->reference_number)->not->toBeNull();
    expect($booking->disclosableReference())->toBeNull();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/bookings')->assertOk()
        ->assertSee($booking->reference_number);
});

it('discloses the reference from the model only after pending', function () {
    // The single place the rule lives, asserted directly so a future caller
    // cannot get a different answer by reading the column itself.
    $booking = refBooking();

    expect($booking->disclosableReference())->toBeNull();

    foreach (['confirmed', 'in_progress', 'completed'] as $status) {
        $booking->update(['status' => $status]);
        expect($booking->fresh()->disclosableReference())->toBe($booking->reference_number);
    }
});
