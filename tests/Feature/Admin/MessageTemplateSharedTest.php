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

it('round-trips the service-due template through the admin editor', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->postJson('/admin/templates/save', [
        'email_subject'       => 'Subject {service}',
        'email_body'          => '<p>Body</p>',
        'sms_body'            => 'Appointment SMS',
        'maintenance_subject' => 'Your {vehicle} needs a look',
        'maintenance_body'    => '<p>Due on {due_date} — {reason}</p>',
        'maintenance_sms'     => 'Service due {due_date}. Try {recommendation}.',
        'channels'            => ['send_email' => true, 'send_sms' => true],
        'triggers'            => ['maintenance_due' => true],
    ])->assertOk();

    $loaded = MessageTemplate::load();

    expect($loaded['maintenance_subject'])->toBe('Your {vehicle} needs a look');
    expect($loaded['maintenance_sms'])->toBe('Service due {due_date}. Try {recommendation}.');
});

it('lets the admin switch service-due reminders off', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);

    // The trigger ships on. A false that is posted must survive the read-back,
    // or the feature cannot be turned off at all: load() merges the saved file
    // over the defaults, so a key left out of the payload comes back true.
    $this->actingAs($admin)->postJson('/admin/templates/save', [
        'email_subject' => 'S',
        'email_body'    => '<p>B</p>',
        'sms_body'      => 'A',
        'triggers'      => ['maintenance_due' => false],
    ])->assertOk();

    expect(MessageTemplate::load()['triggers']['maintenance_due'])->toBeFalse();
});

it('refuses a service-due template saved with an empty body', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->postJson('/admin/templates/save', [
        'email_subject'       => 'S',
        'email_body'          => '<p>B</p>',
        'sms_body'            => 'A',
        'maintenance_subject' => 'Still here',
        'maintenance_body'    => '',
        'maintenance_sms'     => 'Fine',
    ])->assertStatus(422);
});
