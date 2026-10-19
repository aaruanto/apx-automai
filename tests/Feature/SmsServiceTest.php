<?php

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.semaphore.key'         => 'test-secret-key',
        'services.semaphore.sender_name' => 'APXAUTO',
        'services.semaphore.base_url'    => 'https://api.semaphore.co/api/v4',
        'services.semaphore.timeout'     => 5,
    ]);
});

function sms(): SmsService
{
    return app(SmsService::class);
}

/** Semaphore answers with an array of created messages, even for one send. */
function semaphoreAccepted(string $status = 'Queued'): array
{
    return [[
        'message_id'  => 123456,
        'recipient'   => '639171234567',
        'sender_name' => 'APXAUTO',
        'network'     => 'Smart',
        'status'      => $status,
    ]];
}

// ── Number normalisation ─────────────────────────────────────────────────

it('normalises every shape a customer might type', function (string $input) {
    expect(sms()->normalise($input))->toBe('+639171234567');
})->with([
    '09171234567',
    '9171234567',
    '639171234567',
    '+639171234567',
    '0917 123 4567',
    '0917-123-4567',
    '+63 917 123 4567',
    '(0917) 123-4567',
    ' 09171234567 ',
]);

it('refuses anything that is not a Philippine mobile number', function (?string $input) {
    expect(sms()->normalise($input))->toBeNull();
})->with([
    'landline'       => '0281234567',
    'too short'      => '0917123',
    'too long'       => '091711234567',
    'wrong country'  => '+14155551234',
    'starts 8 not 9' => '08171234567',
    'letters'        => 'not a number',
    'empty'          => '',
    'null'           => null,
]);

// ── Segment counting ─────────────────────────────────────────────────────

it('counts a short plain message as one GSM-7 segment', function () {
    $r = sms()->segments('Hi Juan! Your oil change is on March 16 at 9:00 AM.');

    expect($r['encoding'])->toBe('GSM-7');
    expect($r['segments'])->toBe(1);
});

it('splits at 160 GSM-7 characters, then bills 153 per part', function () {
    expect(sms()->segments(str_repeat('a', 160))['segments'])->toBe(1);
    expect(sms()->segments(str_repeat('a', 161))['segments'])->toBe(2);
    expect(sms()->segments(str_repeat('a', 306))['segments'])->toBe(2);
    expect(sms()->segments(str_repeat('a', 307))['segments'])->toBe(3);
});

it('drops to UCS-2 and 70 characters on one non-GSM character', function () {
    // A smart quote, the kind pasted in from Word, is the realistic case.
    $r = sms()->segments('Here’s your reminder');

    expect($r['encoding'])->toBe('UCS-2');
    expect(sms()->segments(str_repeat('a', 70).'’')['segments'])->toBe(2);
});

it('charges the extended GSM characters as the two septets they cost', function () {
    // 159 plain + one '€' = 161 septets, so it tips into a second segment
    // even though it is only 160 characters long.
    $r = sms()->segments(str_repeat('a', 159).'€');

    expect($r['encoding'])->toBe('GSM-7');
    expect($r['length'])->toBe(161);
    expect($r['segments'])->toBe(2);
});

// ── Sending ──────────────────────────────────────────────────────────────

it('posts the key as a form field with the number stripped of its plus', function () {
    Http::fake(['api.semaphore.co/*' => Http::response(semaphoreAccepted(), 200)]);

    $result = sms()->send('0917 123 4567', 'Reminder', ['trigger' => 'remind_1d']);

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.semaphore.co/api/v4/messages'
            && $request->method() === 'POST'
            // A form post, not JSON, and the key is a field rather than a header.
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request['apikey']     === 'test-secret-key'
            && $request['number']     === '639171234567'
            && $request['message']    === 'Reminder'
            && $request['sendername'] === 'APXAUTO';
    });

    $log = SmsLog::sole();
    // E.164 is kept for the record even though the wire format drops the plus.
    expect($log->recipient)->toBe('+639171234567');
    expect($log->reference_id)->toBe('123456');
    expect($log->trigger)->toBe('remind_1d');
    expect($log->status)->toBe('queued');
    expect($log->succeeded())->toBeTrue();
});

it('omits the sender name entirely when none is configured', function () {
    config(['services.semaphore.sender_name' => null]);
    Http::fake(['api.semaphore.co/*' => Http::response(semaphoreAccepted(), 200)]);

    sms()->send('09171234567', 'Reminder');

    // Sending an empty sendername would override the account default with
    // nothing, so the field has to be absent rather than blank.
    Http::assertSent(fn (Request $r) => ! isset($r['sendername']));
});

it('refuses a message starting with TEST, which Semaphore silently discards', function (string $content) {
    Http::fake();

    $result = sms()->send('09171234567', $content);

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('silently discards');
    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe('test_prefix');
})->with([
    'TEST message',
    'test message',
    '  TESTING one two',
]);

it('never sends to a number it could not normalise', function () {
    Http::fake();

    expect(sms()->send('0281234567', 'Reminder')['ok'])->toBeFalse();
    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe('invalid_number');
});

it('refuses a message longer than this app allows', function () {
    Http::fake();

    $result = sms()->send('09171234567', str_repeat('a', 1601));

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('1601');
    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe('too_long');
});

it('treats a message Semaphore marks Failed as a failure', function () {
    // HTTP 200, but the gateway says it did not go. Reporting that as success
    // is exactly the kind of lie this whole feature exists to avoid.
    Http::fake(['api.semaphore.co/*' => Http::response(semaphoreAccepted('Failed'), 200)]);

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
    expect(SmsLog::sole()->succeeded())->toBeFalse();
});

it('explains each failure in terms an admin can act on', function (int $status, string $expected) {
    Http::fake(['api.semaphore.co/*' => Http::response(['message' => 'nope'], $status)]);

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain($expected);
    expect(SmsLog::sole()->status)->toBe('http_'.$status);
})->with([
    [401, 'SEMAPHORE_KEY'],
    [403, 'account-level block'],
    [402, 'Out of Semaphore credits'],
    [422, 'rejected the request'],
    [429, 'Rate limited'],
]);

it('surfaces a validation bag naming the offending field', function () {
    Http::fake(['api.semaphore.co/*' => Http::response([
        'errors' => ['sendername' => ['The selected sendername is invalid.']],
    ], 422)]);

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['message'])->toContain('sendername');
    expect($result['message'])->toContain('invalid');
});

it('falls back to the raw body when the error shape is unfamiliar', function () {
    Http::fake(['api.semaphore.co/*' => Http::response('Sender name not approved', 422)]);

    expect(sms()->send('09171234567', 'Reminder')['message'])
        ->toContain('Sender name not approved');
});

it('does not throw when the gateway is unreachable', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('Could not reach Semaphore');
    expect(SmsLog::sole()->status)->toBe('transport_error');
});

// ── Configuration ────────────────────────────────────────────────────────

it('is switched off rather than broken when no key is set', function () {
    config(['services.semaphore.key' => null]);
    Http::fake();

    expect(sms()->configured())->toBeFalse();
    expect(sms()->send('09171234567', 'Reminder')['message'])->toContain('SEMAPHORE_KEY');
    Http::assertNothingSent();
});

it('stays configured without a sender name, which is optional', function () {
    config(['services.semaphore.sender_name' => null]);

    expect(sms()->configured())->toBeTrue();
    expect(sms()->unavailableReason())->toBeNull();
});

it('reads the remaining credits', function () {
    Http::fake(['api.semaphore.co/api/v4/account*' => Http::response(['credit_balance' => 250], 200)]);

    expect(sms()->credits())->toBe(250);
});

it('reads the credits when the account comes back as a list', function () {
    Http::fake(['api.semaphore.co/api/v4/account*' => Http::response([['credit_balance' => 42]], 200)]);

    expect(sms()->credits())->toBe(42);
});

it('returns null credits rather than failing when the balance cannot be read', function () {
    Http::fake(['api.semaphore.co/api/v4/account*' => Http::response('', 500)]);

    expect(sms()->credits())->toBeNull();
});

// ── Segment cost ──────────────────────────────────────────────────────────
//
// A single character outside GSM-7 drops a segment from 160 characters to 70,
// so one em-dash in the branch name billed every service-due text as three
// segments instead of one. Invisible, and triple the cost on every send.

it('maps typographic characters to their GSM-7 equivalents', function () {
    $sms = app(App\Services\SmsService::class);

    expect($sms->gsmSafe('APX Motors — Tandang Sora'))->toBe('APX Motors - Tandang Sora');
    expect($sms->gsmSafe('Only ₱1,350'))->toBe('Only P1,350');
    expect($sms->gsmSafe('it’s a “deal”'))->toBe('it\'s a "deal"');
    expect($sms->gsmSafe('wait…'))->toBe('wait...');
});

it('leaves genuine content alone rather than mangling it', function () {
    $sms = app(App\Services\SmsService::class);

    // A customer's name is worth more than a segment. A message that really
    // needs Unicode still gets it.
    expect($sms->gsmSafe('Hi Peñaflor'))->toBe('Hi Peñaflor');
});

it('turns a three-segment branch name into one segment', function () {
    $sms = app(App\Services\SmsService::class);

    $raw = 'Hi Juan! Your ABC 1234 is due for servicing on March 16, 2026. '
        .'We suggest Standard Service. Book at APX Motors — Tandang Sora or call (02) 8123-4567.';

    expect($sms->segments($raw)['encoding'])->toBe('UCS-2');
    expect($sms->segments($raw)['segments'])->toBe(3);

    expect($sms->segments($sms->gsmSafe($raw))['encoding'])->toBe('GSM-7');
    expect($sms->segments($sms->gsmSafe($raw))['segments'])->toBe(1);
});

it('keeps both shipped SMS templates to one segment at worst case', function () {
    $sms      = app(App\Services\SmsService::class);
    $defaults = App\Support\MessageTemplate::defaults();

    // The longest service name in the shipped catalogue, and a long first name.
    $sample = [
        '{first_name}'     => 'Maria Cristina',
        '{service}'        => 'Automatic Transmission Service',
        '{date}'           => 'September 30, 2026',
        '{time}'           => '10:30 AM',
        '{reference}'      => 'APX-10042',
        '{plate}'          => 'ABC 1234',
        '{due_date}'       => 'September 30, 2026',
        '{recommendation}' => 'Standard Service',
        '{contact}'        => '(02) 8123-4567',
        '{branch}'         => config('apx.branch_name'),
    ];

    foreach (['sms_body', 'maintenance_sms'] as $key) {
        $rendered = $sms->gsmSafe(strtr($defaults[$key], $sample));
        $parts    = $sms->segments($rendered);

        expect($parts['encoding'])->toBe('GSM-7');
        expect($parts['segments'])->toBe(1, "{$key} rendered to {$parts['segments']} segments");
    }
});
