<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * The Send Test button on Message Templates. It used to flash "Test message
 * sent successfully" unconditionally — on the log driver, with no address
 * given, and when SMTP threw. These tests pin down that it now reports what
 * actually happened, per channel.
 */

// The controller keeps the template as a JSON file on the default disk, so
// without this every test here would read and write the developer's own
// saved template.
beforeEach(fn () => Storage::fake('local'));

function testAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function postTest(array $payload = [])
{
    return test()->actingAs(testAdmin())
        ->postJson('/admin/templates/test', $payload + ['template_type' => '3d']);
}

it('refuses to claim success while the mailer is the log driver', function () {
    config(['mail.default' => 'log']);

    $r = postTest(['test_email' => 'someone@example.com'])->assertOk();

    expect($r->json('ok'))->toBeFalse();
    expect($r->json('results.0.channel'))->toBe('Email');
    expect($r->json('results.0.ok'))->toBeFalse();
    // The message has to name the variable, or the admin has nothing to act on.
    expect($r->json('results.0.message'))->toContain('MAIL_MAILER');
});

it('reports a successful send with the address it went to', function () {
    // The array transport, not Mail::fake() — MailFake does not intercept
    // Mail::html(), so a fake here would attempt a real SMTP connection.
    config(['mail.default' => 'array']);

    $r = postTest(['test_email' => 'someone@example.com'])->assertOk();

    expect($r->json('ok'))->toBeTrue();
    expect($r->json('results.0.ok'))->toBeTrue();
    expect($r->json('results.0.message'))->toContain('someone@example.com');
});

it('says why it skipped rather than reporting a failure', function () {
    config(['mail.default' => 'smtp']);

    // No address at all: skipped, not failed — ok is null, not false.
    $r = postTest()->assertOk();

    expect($r->json('ok'))->toBeTrue();
    expect($r->json('results.0.ok'))->toBeNull();
    expect($r->json('results.0.message'))->toContain('No address given');
});

it('skips email when the channel is switched off in the template', function () {
    config(['mail.default' => 'smtp']);

    Storage::put('private/templates/reminder.json', json_encode([
        'email_subject' => 'S', 'email_preheader' => '', 'email_body' => 'B',
        'sms_body' => 'X',
        'triggers' => [], 'channels' => ['send_email' => false, 'send_sms' => true],
        'sender_name' => 'APX AutoMai', 'reply_to' => '',
    ]));

    $r = postTest(['test_email' => 'someone@example.com'])->assertOk();

    expect($r->json('results.0.ok'))->toBeNull();
    expect($r->json('results.0.message'))->toContain('switched off');
});

it('does not pretend an SMS went out while no gateway exists', function () {
    config(['mail.default' => 'smtp']);

    $r = postTest(['test_phone' => '09171234567'])->assertOk();

    $sms = collect($r->json('results'))->firstWhere('channel', 'SMS');
    expect($sms['ok'])->toBeNull();
    expect($sms['message'])->toContain('No SMS gateway');
});

it('reports the mailers own error instead of throwing a 500', function () {
    config(['mail.default' => 'smtp']);

    // Stand in for a refused App Password or an unreachable host.
    Mail::shouldReceive('html')->once()->andThrow(
        new \Symfony\Component\Mailer\Exception\TransportException('Failed to authenticate on SMTP server')
    );

    $r = postTest(['test_email' => 'someone@example.com'])->assertOk();

    expect($r->json('ok'))->toBeFalse();
    expect($r->json('results.0.message'))->toContain('Failed to authenticate');
});

it('keeps the password out of the reported error but keeps the username in', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.password' => 'super-secret-app-pw',
        'mail.mailers.smtp.username' => 'bot@example.com',
    ]);

    Mail::shouldReceive('html')->once()->andThrow(new \RuntimeException(
        'SMTP said no to bot@example.com with password super-secret-app-pw'
    ));

    $message = postTest(['test_email' => 'someone@example.com'])
        ->assertOk()->json('results.0.message');

    expect($message)->not->toContain('super-secret-app-pw');
    expect($message)->toContain('[redacted]');
    // A 535 is nearly always the wrong username, so it has to stay readable.
    expect($message)->toContain('bot@example.com');
});

it('keeps the test endpoint away from customers', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->postJson('/admin/templates/test', ['template_type' => '3d'])
        ->assertStatus(403);
});
