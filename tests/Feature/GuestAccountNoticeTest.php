<?php

/**
 * What the customer is TOLD about the account their booking just made.
 *
 * The backend was right all along: a guest booking creates the account and
 * emails a link, and registering with that address is refused. What was
 * missing was every word of that reaching the person at the keyboard.
 *
 *  - "Create a free account" carried an inline onclick that sent the browser
 *    to /register while the booking POST was still in flight. The booking was
 *    saved, the reference number was never shown, and they landed on a form
 *    that refuses their own address — because by then they have a guest
 *    account. That is what a bug report of "it let me register twice" was
 *    actually describing.
 *  - The success screen then showed one fixed line for everybody, so even
 *    without the redirect nobody was told a setup link had been emailed.
 *  - A returning unclaimed guest got no link at all, the first one having
 *    expired after an hour.
 *
 * The rule these pin: the screen says a link was sent exactly when one was.
 */

use App\Mail\GuestAccountCreated;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function noticeService(): Service
{
    return Service::create(['name' => 'Notice Svc '.uniqid(), 'price' => 1500, 'duration' => 45]);
}

function bookGuest(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/booking/guest', array_merge([
        'guest_name'    => 'Juan Dela Cruz',
        'guest_email'   => 'notice@example.com',
        'guest_phone'   => '09171234567',
        'vehicle_make'  => 'Toyota',
        'vehicle_model' => 'Vios',
        'vehicle_year'  => 2021,
        'vehicle_plate' => 'NTC 1111',
        'vehicle_type'  => 'car',
        'service_ids'   => [noticeService()->id],
        'booking_date'  => nextWeekday(),
        'booking_time'  => '10:00',
    ], $overrides));
}

/**
 * Mail::fake() cannot see these.
 *
 * MailFake::sendMail returns early for anything that is not a Mailable, and
 * the suite reads delivered mail off the array transport everywhere else for
 * the same reason. Counting what the transport actually carried is also the
 * only way to tell "a link was sent" from "the flag said so".
 *
 * One request per test, always.
 *
 * These mails are sent from dispatch(...)->afterResponse(), which registers an
 * application terminating callback, and Application::terminate() never clears
 * that array. A test that makes two requests therefore runs the FIRST
 * request's mail closure a second time on the second request, and the mailbox
 * shows BK-0001's mail twice. That is a quirk of reusing one application
 * across requests, not of this feature — a real request lives in its own
 * process — but it makes any count across two requests meaningless. So a
 * returning guest is set up by creating their row, never by booking twice.
 */
function setupLinksSent(): int
{
    $sent = 0;

    foreach (Mail::getSymfonyTransport()->messages() as $message) {
        if (str_contains($message->getOriginalMessage()->getSubject(), 'Set Up Your')) {
            $sent++;
        }
    }

    return $sent;
}

// ── The flag means what it says ──────────────────────────────────────────

it('reports that a setup link was emailed to a brand new guest', function () {
    bookGuest(['create_account' => true])
        ->assertOk()
        ->assertJsonPath('account_setup_email', true);

    expect(setupLinksSent())->toBe(1);
});

it('reports it even when they did not ask for an account', function () {
    // The account is created either way, so the link goes out either way.
    // Saying nothing left them with an account they never knew about.
    bookGuest()
        ->assertOk()
        ->assertJsonPath('create_account', false)
        ->assertJsonPath('account_setup_email', true);

    expect(setupLinksSent())->toBe(1);
});

/**
 * The guest this flow would have left behind on an earlier visit.
 *
 * Made directly rather than by booking first, and that is deliberate: see the
 * note on setupLinksSent(). One request per test is the only way to say whose
 * mail is whose.
 */
function existingGuest(string $email = 'notice@example.com'): User
{
    return User::create([
        'name'      => 'Juan Dela Cruz',
        'email'     => $email,
        'phone'     => '09171234567',
        'role'      => 'customer',
        'password'  => bcrypt('irrelevant-random'),
        'is_active' => true,
        'is_guest'  => true,
    ]);
}

it('does not claim a link was sent when a returning guest did not ask', function () {
    existingGuest();

    bookGuest()
        ->assertOk()
        ->assertJsonPath('account_setup_email', false);

    expect(setupLinksSent())->toBe(0);
});

it('sends a returning unclaimed guest a fresh link when they ask for one', function () {
    // No outstanding token: the link from their last visit has expired, which
    // after an hour is the normal case. Without this re-send, someone who
    // missed the first mail had a half-made account and no way to reach it,
    // however often they booked.
    existingGuest();

    bookGuest(['create_account' => true])
        ->assertOk()
        ->assertJsonPath('account_setup_email', true);

    expect(setupLinksSent())->toBe(1);
});

it('does not promise a second link while the first is still fresh', function () {
    $guest = existingGuest();

    // A link they were sent moments ago. recentlyCreatedToken is the broker's
    // own throttle; the screen has to agree with it, or it promises an inbox
    // something that is not coming.
    Password::createToken($guest);

    bookGuest(['create_account' => true])
        ->assertOk()
        ->assertJsonPath('account_setup_email', false);

    expect(setupLinksSent())->toBe(0);
});

it('never offers a setup link for an address that belongs to a real account', function () {
    User::factory()->create([
        'email' => 'notice@example.com', 'role' => 'customer', 'is_guest' => false,
    ]);

    bookGuest(['create_account' => true])->assertStatus(422);

    expect(setupLinksSent())->toBe(0);
});

// ── The modal has to use it ──────────────────────────────────────────────

it('shows the account notice only when the server says a link was sent', function () {
    $page = $this->get('/')->assertOk()->getContent();

    // Keyed off what was done, not off the checkbox: a returning guest can
    // tick the box and be throttled.
    expect($page)->toContain('data.account_setup_email');
    expect($page)->toContain('id="apx-success-account"');
    expect($page)->toContain('id="apx-success-email"');

    // Hidden until the handler says otherwise.
    expect($page)->toMatch('/id="apx-success-account"[^>]*style="display:none;/');
});

it('tells them how long the link lasts, from config rather than a guess', function () {
    config(['auth.passwords.users.expire' => 77]);

    expect($this->get('/')->getContent())->toContain('expires in 77 minutes');
});

it('does not send the account button off to /register', function () {
    // The inline onclick fired alongside the submit listener, so one press
    // both posted the booking and navigated away before the response landed.
    // /register is the wrong destination regardless: it refuses an address
    // that already has a guest account, which is every guest who gets here.
    $page = $this->get('/')->getContent();

    expect($page)->not->toMatch('/<button[^>]*class="apx-btn-member"[^>]*onclick=/');
    expect($page)->toContain('<button type="button" class="apx-btn-member">');
});
