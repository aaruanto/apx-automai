<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The scheduler trigger.
 *
 * This endpoint exists because Render's free tier has no cron jobs, no worker
 * process and no shell, so nothing in routes/console.php ever ticked in
 * production. It is also, by design, an unauthenticated URL that makes the app
 * send mail — so the guard around it is the whole story.
 */

it('404s when no token is configured', function () {
    config(['apx.cron_token' => '']);

    // Not 403: a deploy that has not set the variable should look like the
    // route does not exist, rather than advertising that it is there and
    // waiting for the right secret.
    $this->get('/cron/schedule')->assertNotFound();
});

it('404s on a wrong token', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron/schedule?token=wrong')->assertNotFound();
});

it('404s when the token is missing entirely', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron/schedule')->assertNotFound();
});

it('runs the scheduler with the right token in the query string', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron/schedule?token=correct-horse-battery-staple')
        ->assertOk()
        ->assertSee('ok schedule');
});

it('accepts the token as a header, so it stays out of access logs', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->withHeader('X-Cron-Token', 'correct-horse-battery-staple')
        ->get('/cron/schedule')
        ->assertOk();
});

it('defaults to the schedule task', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    $this->get('/cron?token=correct-horse-battery-staple')
        ->assertOk()
        ->assertSee('ok schedule');
});

it('refuses a task that is not on the allow list', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);

    // The task is an allow-list key, never a command name taken from the
    // request: otherwise a leaked token would be the ability to run any
    // artisan command on production, migrate:fresh included.
    $this->get('/cron/migrate:fresh?token=correct-horse-battery-staple')
        ->assertNotFound();
});

it('keeps working while the site is in maintenance mode', function () {
    config(['apx.cron_token' => 'correct-horse-battery-staple']);
    \App\Models\Setting::set('maintenance_mode', true);

    // Reminders for bookings the shop has already taken should keep going out
    // while the customer-facing site is switched off.
    $this->get('/cron/schedule?token=correct-horse-battery-staple')->assertOk();

    \App\Models\Setting::set('maintenance_mode', false);
});
