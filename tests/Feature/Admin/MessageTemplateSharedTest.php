<?php

use App\Support\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

/**
 * The admin editor and the reminders command each used to carry their own copy
 * of the template path, and the two did not match: the panel wrote
 * "private/templates/reminder.json" while the scheduler read
 * "templates/reminder.json". Editing a template therefore changed nothing
 * about the messages customers actually received.
 */

it('reads back exactly what the admin editor saved', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->postJson('/admin/templates/save', [
        'email_subject' => 'Subject {service}',
        'email_body'    => '<p>Body</p>',
        'sms_body'      => 'A distinctly edited SMS body for {name}.',
        'channels'      => ['send_email' => true, 'send_sms' => true],
        'triggers'      => ['remind_1d' => true],
        'sender_name'   => 'APX AutoMai',
    ])->assertOk();

    // What the scheduler would load on its next run.
    expect(MessageTemplate::load()['sms_body'])
        ->toBe('A distinctly edited SMS body for {name}.');
});

it('falls back to defaults rather than breaking on a corrupt file', function () {
    Storage::put(MessageTemplate::PATH, '{ this is not json');

    expect(MessageTemplate::load()['sms_body'])
        ->toBe(MessageTemplate::defaults()['sms_body']);
});

it('fills in keys missing from an older saved file', function () {
    // A file saved before email_preheader existed must not produce an
    // undefined-key error on load.
    Storage::put(MessageTemplate::PATH, json_encode(['sms_body' => 'Only this key']));

    $loaded = MessageTemplate::load();

    expect($loaded['sms_body'])->toBe('Only this key');
    expect($loaded['email_subject'])->toBe(MessageTemplate::defaults()['email_subject']);
    expect($loaded['channels']['send_sms'])->toBeTrue();
});

it('resolves the shop placeholders that used to be null', function () {
    // The reminders command referenced apx.branch_name and apx.contact_number
    // before any config file defined them, so {branch} rendered empty.
    expect(config('apx.branch_name'))->not->toBeNull();
    expect(config('apx.contact_number'))->not->toBeNull();
});
