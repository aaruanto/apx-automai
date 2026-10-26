<?php

use App\Models\SmsLog;
use App\Services\Sms\LogGateway;
use App\Services\Sms\SemaphoreGateway;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\UniSmsGateway;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * SMS_DRIVER, and the log driver it defaults to.
 *
 * The provider became a config value because a Philippine carrier has to
 * approve an alphanumeric sender name before a branded text can be delivered,
 * and that approval is weeks of paperwork from a third party. These tests pin
 * down the two things that makes worth having: that naming a driver selects
 * one, and that the default one runs every rule a real send runs while
 * contacting nobody and billing nothing.
 *
 * Tests for Semaphore's own behaviour live in SmsServiceTest.
 */
function driver(string $name): SmsService
{
    config(['services.sms.driver' => $name]);

    // Rebuilt rather than reused: the binding reads config when it resolves,
    // so a service already in the container still holds the old gateway.
    app()->forgetInstance(SmsService::class);
    app()->forgetInstance(SmsGateway::class);

    return app(SmsService::class);
}

// ── Selection ──────────────────────────────────────────────────────────────

it('sends nothing unless a driver says otherwise', function () {
    // The shipped default. A fresh clone, a new developer's machine and a
    // deploy that forgets the variable all land here, and none of them should
    // be able to text a real customer by accident.
    expect(config('services.sms.driver'))->toBe('log');
});

it('hands SmsService the gateway SMS_DRIVER names', function (string $name, string $class) {
    expect(driver($name)->driver())->toBe($name)
        ->and(app(SmsGateway::class))->toBeInstanceOf($class);
})->with([
    ['log', LogGateway::class],
    ['unisms', UniSmsGateway::class],
    ['semaphore', SemaphoreGateway::class],
]);

it('falls back to log when SMS_DRIVER is a typo', function () {
    // Not an exception. Mistyping an environment variable should stop texts
    // going out, not return a 500 on every page that touches the service.
    expect(driver('semafore')->driver())->toBe('log');
});

// ── The log driver ─────────────────────────────────────────────────────────

it('contacts nobody at all on the log driver', function () {
    Http::fake();

    expect(driver('log')->send('09171234567', 'Your APX Motors booking is confirmed.')['ok'])->toBeTrue();

    Http::assertNothingSent();
});

it('is configured with no credentials whatsoever', function () {
    // The point of the driver: reminders have to actually run during
    // development, and SmsService skips the channel entirely when the gateway
    // reports itself unavailable.
    config(['services.semaphore.key' => null, 'services.unisms.key' => null]);

    $sms = driver('log');

    expect($sms->configured())->toBeTrue()
        ->and($sms->unavailableReason())->toBeNull();
});

it('writes a simulated send to sms_logs so the reminder can be audited', function () {
    $result = driver('log')->send('0917 123 4567', 'Your APX Motors booking is confirmed.');

    expect($result['ok'])->toBeTrue();

    $log = SmsLog::sole();

    // 'simulated', not 'queued' or 'sent'. Anyone reading this table later
    // must be able to tell that nothing left the building.
    expect($log->status)->toBe('simulated')
        // Normalised by SmsService, not the gateway, so the stored number is
        // the same shape whoever carries the message.
        ->and($log->recipient)->toBe('+639171234567')
        ->and($log->segments)->toBe(1)
        ->and($log->reference_id)->toStartWith('sim-')
        ->and($log->fail_reason)->toBeNull();
});

it('says plainly that a simulated send was not a send', function () {
    // This string reaches an admin through the Send Test button. "Sent
    // successfully" there, on this driver, is the bug the driver exists to
    // make impossible.
    expect(driver('log')->send('09171234567', 'Booking confirmed.')['message'])
        ->toContain('Simulated')
        ->toContain('SMS_DRIVER');
});

it('still rejects what a real provider would reject', function () {
    $sms = driver('log');

    // A simulator that accepts a landline teaches the wrong thing: the number
    // rules have to fail here exactly as they would on Semaphore.
    expect($sms->send('02 8123 4567', 'Booking confirmed.')['ok'])->toBeFalse()
        ->and(SmsLog::sole()->status)->toBe('invalid_number');
});

it('still counts what a real message would cost', function () {
    // Segment counting is the whole reason an em-dash matters, and it has to
    // keep working on the driver used while writing templates.
    $result = driver('log')->send('09171234567', str_repeat('A', 200));

    expect($result['ok'])->toBeTrue()
        ->and(SmsLog::sole()->segments)->toBe(2);
});

it('does not refuse a TEST message the way Semaphore does', function () {
    // Deliberately asymmetric. Semaphore discards anything starting with
    // "TEST" and reports success, so SemaphoreGateway refuses it up front —
    // but a simulator that rejects more than the real thing sends you hunting
    // a bug that exists only in the simulator.
    expect(driver('log')->send('09171234567', 'TEST message')['ok'])->toBeTrue()
        ->and(SmsLog::sole()->status)->toBe('simulated');
});

it('reports no balance, because nothing is spent', function () {
    expect(driver('log')->credits())->toBeNull();
});

// ── UniSMS ─────────────────────────────────────────────────────────────────

it('names the missing UniSMS variable rather than just switching off', function (array $config, ?string $expected) {
    config($config + [
        'services.unisms.key'       => null,
        'services.unisms.sender_id' => null,
    ]);

    expect(driver('unisms')->unavailableReason())->toBe($expected);
})->with([
    'neither set'   => [[], 'UNISMS_KEY is not set, so SMS is switched off.'],
    'key only'      => [
        ['services.unisms.key' => 'k'],
        'UNISMS_SENDER_ID is not set. UniSMS requires a sender ID on every message.',
    ],
    'both set'      => [
        ['services.unisms.key' => 'k', 'services.unisms.sender_id' => 'APXMOTORS'],
        null,
    ],
]);

it('sends through UniSMS with the key as basic auth, not a form field', function () {
    config([
        'services.unisms.key'       => 'test-secret-key',
        'services.unisms.sender_id' => 'APXMOTORS',
        'services.unisms.base_url'  => 'https://unismsapi.com/api',
        'services.unisms.timeout'   => 5,
    ]);

    Http::fake([
        'unismsapi.com/*' => Http::response([
            'message' => ['reference_id' => 'ref-9001', 'status' => 'pending'],
        ], 200),
    ]);

    $result = driver('unisms')->send('09171234567', 'Your APX Motors booking is confirmed.');

    expect($result['ok'])->toBeTrue();

    Http::assertSent(function ($request) {
        // The difference from Semaphore that would otherwise be found only by
        // a 401 in production: the key is an Authorization header here.
        expect($request->hasHeader('Authorization'))->toBeTrue();

        return $request->url() === 'https://unismsapi.com/api/sms'
            && $request['recipient'] === '+639171234567'
            && $request['sender_id'] === 'APXMOTORS';
    });

    // Read from "message", a single object — Semaphore returns an array.
    expect(SmsLog::sole()->reference_id)->toBe('ref-9001');
});

it('does not log a UniSMS send as queued when UniSMS refused it', function () {
    config([
        'services.unisms.key'       => 'test-secret-key',
        'services.unisms.sender_id' => 'APXMOTORS',
        'services.unisms.base_url'  => 'https://unismsapi.com/api',
        'services.unisms.timeout'   => 5,
    ]);

    Http::fake([
        'unismsapi.com/*' => Http::response(['message' => 'sender_id is not registered'], 422),
    ]);

    $result = driver('unisms')->send('09171234567', 'Booking confirmed.');

    expect($result['ok'])->toBeFalse()
        // The gateway explained itself, so quote it rather than appending a
        // guess that would read as a contradiction.
        ->and($result['message'])->toContain('sender_id is not registered')
        ->and(SmsLog::sole()->status)->toBe('http_422');
});
