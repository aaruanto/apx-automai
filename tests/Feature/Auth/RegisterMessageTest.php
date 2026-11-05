<?php

/**
 * What /register tells someone whose address is already taken.
 *
 * The form was refusing correctly and saying so in a way that read like
 * acceptance: "If an account can be set up for that address, we have just
 * emailed a link to it. Please check your inbox to finish signing up." On a
 * redirect back to an empty form, that is indistinguishable from success, and
 * it was read that way — as the system having allowed a second account on one
 * address, which it never did.
 *
 * Two further faults sat underneath it. The view rendered $errors->first(),
 * one message in field order, so a blank name hid the explanation entirely.
 * And offerTheRightLinkTo() ran before any validation, so that invisible
 * refusal still sent real mail.
 *
 * None of this may distinguish a guest address from a registered one. That
 * difference is what would turn the form into an email enumerator, and
 * GuestAccountClaimTest pins the two messages as identical.
 */

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function messagesSent(): int
{
    return Mail::getSymfonyTransport()->messages()->count();
}

function guestRow(string $email): User
{
    return User::create([
        'name' => 'Juan Dela Cruz', 'email' => $email, 'role' => 'customer',
        'password' => bcrypt('random-nobody-has-seen'), 'is_guest' => true,
    ]);
}

function registerWith(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->from('/register')->post('/register', array_merge([
        'first_name' => 'Juan',
        'last_name'  => 'Dela Cruz',
        'email'      => 'taken@example.com',
        'phone'      => '09171234567',
        'password'   => 'Str0ng-Passw0rd!',
        'password_confirmation' => 'Str0ng-Passw0rd!',
    ], $overrides));
}

// ── It must not read as "you are done" ───────────────────────────────────

it('leads with the fact that no account was created', function () {
    guestRow('taken@example.com');

    registerWith()->assertSessionHasErrors('email');

    $message = session('errors')->first('email');

    // The old wording opened with an offer and never said what had happened.
    expect($message)->toContain('did not create');
    expect($message)->not->toContain('finish signing up');
});

it('does not claim mail was just sent, which the broker throttle can make false', function () {
    guestRow('taken@example.com');

    registerWith();

    // A second attempt inside the throttle window sends nothing at all, so a
    // message in the past tense was simply untrue there.
    expect(session('errors')->first('email'))->not->toContain('just emailed');
});

it('says the same thing whether the address is a guest or a real account', function () {
    // Restated here because it constrains every word above: the two branches
    // must stay indistinguishable or the form enumerates customers.
    guestRow('guest@example.com');
    User::factory()->create(['email' => 'real@example.com', 'role' => 'customer']);

    registerWith(['email' => 'guest@example.com']);
    $guestMessage = session('errors')->first('email');

    registerWith(['email' => 'real@example.com']);
    $realMessage = session('errors')->first('email');

    expect($guestMessage)->toBe($realMessage);
    expect($guestMessage)->not->toContain('guest');
});

// ── It must be visible ───────────────────────────────────────────────────

it('never leaves the explanation buried under another field', function () {
    guestRow('taken@example.com');

    // A blank name stops at the first pass, before uniqueness is considered.
    // So the address message is not raised at all here — and crucially no mail
    // is sent either, which is the state this ordering exists to produce. It
    // used to raise both and render only the first, so the customer was
    // emailed a link and told nothing but "The first name field is required."
    registerWith(['first_name' => ''])->assertSessionHasErrors('first_name');
    expect(session('errors')->has('email'))->toBeFalse();

    // Fix the name and the address message arrives on its own, where nothing
    // can outrank it.
    registerWith()->assertSessionHasErrors('email');

    $errors = session('errors');
    expect($errors->first('email'))->toContain('did not create');
    expect($errors->count())->toBe(1);
});

it('renders them all on the page, not only the first', function () {
    $html = $this->withSession(['errors' => tap(new \Illuminate\Support\MessageBag([
        'first_name' => ['The first name field is required.'],
        'email'      => ['We did not create a new account.'],
    ]), fn ($bag) => $bag)])->get('/register')->getContent();

    expect($html)->toContain('The first name field is required.');
    expect($html)->toContain('We did not create a new account.');
});

// ── It must not send mail for a form it is going to reject anyway ────────

it('sends nothing when another field is invalid', function () {
    guestRow('taken@example.com');

    registerWith(['first_name' => ''])->assertSessionHasErrors('first_name');

    // The customer saw only "The first name field is required." while a real
    // setup link went to their inbox, explained by nothing on screen.
    expect(messagesSent())->toBe(0);
});

it('still sends the link once the rest of the form is sound', function () {
    guestRow('taken@example.com');

    registerWith()->assertSessionHasErrors('email');

    expect(messagesSent())->toBe(1);
});

it('creates nothing, whatever it says', function () {
    guestRow('taken@example.com');

    registerWith();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
    expect(User::where('email', 'taken@example.com')->sole()->is_guest)->toBeTrue();
});

it('still lets a genuinely new address through', function () {
    registerWith(['email' => 'nobody-has-this@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    expect(User::where('email', 'nobody-has-this@example.com')->count())->toBe(1);
});
