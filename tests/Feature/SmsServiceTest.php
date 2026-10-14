<?php

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.unisms.key'       => 'test-secret-key',
        'services.unisms.sender_id' => 'APXAUTO',
        'services.unisms.base_url'  => 'https://unismsapi.com/api',
        'services.unisms.timeout'   => 5,
    ]);
});

function sms(): SmsService
{
    return app(SmsService::class);
}

/** The documented 201 body. */
function unismsAccepted(string $status = 'pending'): array
{
    return ['message' => [
        'status'       => $status,
        'reference_id' => 'msg_84e8b93b-6315-46af-a686',
        'recipient'    => '+639171234567',
        'sender_id'    => 'APXAUTO',
        'fail_reason'  => null,
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
    'landline'            => '0281234567',
    'too short'           => '0917123',
    'too long'            => '091711234567',
    'wrong country'       => '+14155551234',
    'starts 8 not 9'      => '08171234567',
    'letters'             => 'not a number',
    'empty'               => '',
    'null'                => null,
]);

// ── Segment counting ─────────────────────────────────────────────────────

it('counts a short plain message as one GSM-7 segment', function () {
    $r = sms()->segments('Hi Juan! Your oil change is on March 16 at 9:00 AM.');

    expect($r['encoding'])->toBe('GSM-7');
    expect($r['segments'])->toBe(1);
});

it('splits at 160 GSM-7 characters, then bills 153 per part', function () {
    expect(sms()->segments(str_repeat('a', 160))['segments'])->toBe(1);
    // 161 no longer fits one part, so it is billed as two of 153.
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

it('posts the documented body with basic auth and logs the reference', function () {
    Http::fake(['unismsapi.com/*' => Http::response(unismsAccepted(), 201)]);

    $result = sms()->send('0917 123 4567', 'Reminder', ['trigger' => 'remind_1d']);

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://unismsapi.com/api/sms'
            && $request->method() === 'POST'
            && $request['recipient'] === '+639171234567'
            && $request['content']   === 'Reminder'
            && $request['sender_id'] === 'APXAUTO'
            // Basic auth, key as username and an empty password.
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-secret-key:'));
    });

    $log = SmsLog::sole();
    expect($log->recipient)->toBe('+639171234567');
    expect($log->reference_id)->toBe('msg_84e8b93b-6315-46af-a686');
    expect($log->trigger)->toBe('remind_1d');
    expect($log->succeeded())->toBeTrue();
});

it('never sends to a number it could not normalise', function () {
    Http::fake();

    $result = sms()->send('0281234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe('invalid_number');
});

it('refuses a message longer than the gateway accepts', function () {
    Http::fake();

    $result = sms()->send('09171234567', str_repeat('a', 671));

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('671');
    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe('too_long');
});

it('explains each documented failure in terms an admin can act on', function (int $status, string $expected) {
    Http::fake(['unismsapi.com/*' => Http::response(['message' => 'nope'], $status)]);

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain($expected);
    expect(SmsLog::sole()->status)->toBe('http_'.$status);
})->with([
    [401, 'UNISMS_KEY'],
    [422, 'sender ID'],
    [429, 'Rate limited'],
]);

it('does not throw when the gateway is unreachable', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

    $result = sms()->send('09171234567', 'Reminder');

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('Could not reach UniSMS');
    expect(SmsLog::sole()->status)->toBe('transport_error');
});

// ── Configuration ────────────────────────────────────────────────────────

it('is switched off rather than broken when no key is set', function () {
    config(['services.unisms.key' => null]);
    Http::fake();

    expect(sms()->configured())->toBeFalse();
    expect(sms()->send('09171234567', 'Reminder')['message'])->toContain('UNISMS_KEY');
    Http::assertNothingSent();
});

it('names the sender id when that is what is missing', function () {
    config(['services.unisms.sender_id' => null]);

    expect(sms()->unavailableReason())->toContain('UNISMS_SENDER_ID');
});

it('reads the remaining credits', function () {
    Http::fake(['unismsapi.com/api/account' => Http::response(['sms_credits' => 250], 200)]);

    expect(sms()->credits())->toBe(250);
});

it('returns null credits rather than failing when the balance cannot be read', function () {
    Http::fake(['unismsapi.com/api/account' => Http::response('', 500)]);

    expect(sms()->credits())->toBeNull();
});

it('surfaces a validation bag naming the offending field', function () {
    Http::fake(['unismsapi.com/*' => Http::response([
        'errors' => ['sender_id' => ['The selected sender id is invalid.']],
    ], 422)]);

    $result = sms()->send('09171234567', 'Reminder');

    // Without this the admin saw only "rejected the request", which is the one
    // case where the gateway's own detail is the entire answer.
    expect($result['message'])->toContain('sender_id');
    expect($result['message'])->toContain('invalid');
});

it('falls back to the raw body when the error shape is unfamiliar', function () {
    Http::fake(['unismsapi.com/*' => Http::response('Sender ID not approved', 422)]);

    expect(sms()->send('09171234567', 'Reminder')['message'])
        ->toContain('Sender ID not approved');
});
