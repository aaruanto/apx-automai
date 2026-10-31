<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * When the shop books for a customer, the customer is told.
 *
 * store() ended with notifyAdminsOfBooking(), which sends requested to admins
 * only — and requested is deliberately not in BookingNotification::MAILED. So
 * a customer whose booking the shop created, a walk-in or a rebook from the
 * cancelled list, got nothing at all: no email and not even a bell entry. They
 * found out by signing in and noticing, or by not turning up.
 *
 * The guest form already emails BookingReceived. A customer booking from their
 * own dashboard pressed the button and saw the confirmation. Admin is the one
 * creator where the customer did not choose the appointment.
 *
 * Mail::fake() is not used here: MailFake::sendMail returns early for anything
 * that is not a Mailable, so a suite using it reads zero however much mail was
 * sent. The array transport is the only honest counter — see CLAUDE.md.
 */
beforeEach(function () {
    config()->set('mail.default', 'array');
    Mail::purge('array');
});

/** Every message actually handed to the transport. */
function anSent(): array
{
    return Mail::getSymfonyTransport()->messages()->all();
}

function anSubjects(): array
{
    return array_map(fn ($m) => $m->getOriginalMessage()->getSubject(), anSent());
}

function anAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function anPayload(array $overrides = []): array
{
    static $n = 1;

    $service = Service::create(['name' => 'Notice '.$n, 'price' => 1200, 'duration' => 60]);

    return array_merge([
        'customer_name'  => 'Juan dela Cruz',
        'customer_phone' => '0917000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'customer_email' => 'juan'.$n++.'@gmail.com',
        'plate'          => 'ANB '.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'service_id'     => $service->id,
        'booking_date'   => nextWeekday(),
        'booking_time'   => '10:00',
    ], $overrides);
}

// ── the gap ───────────────────────────────────────────────────────────────

it('emails the customer when an admin books for them', function () {
    $this->actingAs(anAdmin())->post('/admin/bookings', anPayload())->assertRedirect();

    $booking = Booking::sole();

    expect(anSent())->toHaveCount(1);
    expect(anSubjects()[0])->toContain('Booking Received');
    expect(anSubjects()[0])->toContain($booking->reference_number);
});

it('gives the customer a bell entry as well as the email', function () {
    $this->actingAs(anAdmin())->post('/admin/bookings', anPayload())->assertRedirect();

    $booking  = Booking::sole();
    $customer = $booking->user;

    // The history should show the booking whether or not the inbox does.
    expect($customer->notifications()->count())->toBe(1);
    expect($customer->notifications()->first()->data['title'])->toBe('Booking received');
});

it('words the customers entry for the customer, not the admins', function () {
    $this->actingAs(anAdmin())->post('/admin/bookings', anPayload())->assertRedirect();

    $booking = Booking::sole();

    $customerEntry = $booking->user->notifications()->first()->data;
    $adminEntry    = User::where('role', 'admin')->sole()->notifications()->first()->data;

    expect($customerEntry['body'])->toContain('We have your booking');
    expect($adminEntry['body'])->toContain('requested');
    expect($customerEntry['body'])->not->toBe($adminEntry['body']);
});

it('still tells the admins', function () {
    $admin = anAdmin();

    $this->actingAs($admin)->post('/admin/bookings', anPayload())->assertRedirect();

    // Admin behaviour is unchanged: a bell entry, and no email, because
    // mailing every admin on every request is the noise MAILED rules out.
    expect($admin->notifications()->count())->toBe(1);
    expect(anSubjects())->not->toContain('New booking request');
});

// ── the placeholder address ──────────────────────────────────────────────

it('mails nobody for a walk-in with no email, and still books it', function () {
    $this->actingAs(anAdmin())
        ->post('/admin/bookings', anPayload(['customer_email' => null]))
        ->assertRedirect();

    $booking = Booking::sole();

    // store() synthesises name@apxautomai.local so the account can hold
    // bookings. Nobody reads it, and mailing it fails slowly against a real
    // SMTP server.
    expect($booking->user->email)->toEndWith('@apxautomai.local');
    expect(anSent())->toHaveCount(0);

    // The booking is the point; the email is a courtesy.
    expect($booking->exists)->toBeTrue();

    // And the bell entry is still written, because that costs nothing and the
    // customer may yet claim the account.
    expect($booking->user->notifications()->count())->toBe(1);
});

it('asks the notification class about the address rather than copying the rule', function () {
    $real        = User::factory()->make(['email' => 'someone@gmail.com']);
    $placeholder = User::factory()->make(['email' => 'juandelacruz@apxautomai.local']);
    $blank       = User::factory()->make(['email' => '']);

    expect(BookingNotification::hasRealAddress($real))->toBeTrue();
    expect(BookingNotification::hasRealAddress($placeholder))->toBeFalse();
    expect(BookingNotification::hasRealAddress($blank))->toBeFalse();
    expect(BookingNotification::hasRealAddress(null))->toBeFalse();
});

// ── no double-send ───────────────────────────────────────────────────────

it('sends exactly two distinct emails across booking then confirming', function () {
    $admin = anAdmin();

    $this->actingAs($admin)->post('/admin/bookings', anPayload())->assertRedirect();

    $booking = Booking::sole();
    expect(anSent())->toHaveCount(1);

    $this->actingAs($admin)->put('/admin/bookings/'.$booking->id, [
        'service_ids'  => $booking->services->pluck('id')->all(),
        'booking_date' => $booking->booking_date,
        'booking_time' => $booking->booking_time,
        'status'       => 'confirmed',
    ])->assertRedirect();

    $subjects = anSubjects();

    // "We have your booking", then "it is confirmed" — one of each, never two
    // of either. A double-email on confirm was fixed in c9b3c36c and must
    // stay fixed.
    expect($subjects)->toHaveCount(2);
    expect(collect($subjects)->filter(fn ($s) => str_contains($s, 'Booking Received')))->toHaveCount(1);
    expect(collect($subjects)->filter(fn ($s) => str_contains($s, 'Confirmed')))->toHaveCount(1);
});

// ── mail must never cost the booking ─────────────────────────────────────

it('still creates the booking when the mail transport throws', function () {
    // Nothing on Render runs a queue worker, so this sends inline. A Brevo
    // outage must leave the booking written rather than turn it into a 500 on
    // a form the admin has already filled in.
    config()->set('mail.default', 'failing');
    config()->set('mail.mailers.failing', ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]);

    $this->actingAs(anAdmin())
        ->post('/admin/bookings', anPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $booking = Booking::sole();

    expect($booking->exists)->toBeTrue();
    expect($booking->status)->toBe('pending');

    // The bell entry is written before the mail is attempted, so it survives.
    expect($booking->user->notifications()->count())->toBe(1);
});
