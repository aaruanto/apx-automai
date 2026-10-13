<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\SchedulerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The scheduler status shown on the Message Templates screen.
 *
 * It used to be asserted rather than measured: a hard-coded "Running" badge, a
 * last run of now(), and a countdown to a fixed 8:00 AM that had nothing to do
 * with the schedule — which is how it could claim a per-minute scheduler and a
 * ten-hour wait at the same time.
 */

function schedAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('reports not running when the scheduler has never reported in', function () {
    expect(SchedulerStatus::isRunning())->toBeFalse();
    expect(SchedulerStatus::lastRun())->toBeNull();
    expect(SchedulerStatus::nextRun())->toBeNull();
});

it('reports running on a fresh heartbeat', function () {
    Setting::set('scheduler_last_run', now()->toIso8601String());
    Setting::flushCache();

    expect(SchedulerStatus::isRunning())->toBeTrue();
    expect(SchedulerStatus::nextRun())->not->toBeNull();
});

it('reports not running once the heartbeat goes stale', function () {
    Setting::set('scheduler_last_run', now()->subMinutes(30)->toIso8601String());
    Setting::flushCache();

    // The timestamp is still there, but it is old: a stopped scheduler must
    // not keep reporting health from its last breath.
    expect(SchedulerStatus::lastRun())->not->toBeNull();
    expect(SchedulerStatus::isRunning())->toBeFalse();
    expect(SchedulerStatus::nextRun())->toBeNull();
});

it('tolerates a single missed tick', function () {
    Setting::set('scheduler_last_run', now()->subMinutes(2)->toIso8601String());
    Setting::flushCache();

    expect(SchedulerStatus::isRunning())->toBeTrue();
});

it('survives a corrupt stored value', function () {
    Setting::set('scheduler_last_run', 'not-a-date');
    Setting::flushCache();

    expect(SchedulerStatus::lastRun())->toBeNull();
    expect(SchedulerStatus::isRunning())->toBeFalse();
});

// ── What the admin screen shows ─────────────────────────────────────────

it('shows Not running on the templates page when nothing is scheduled', function () {
    $this->actingAs(schedAdmin())->get('/admin/templates')->assertOk()
        ->assertSee('Not running')
        ->assertSee('never reported in', false);
});

it('shows Running once a heartbeat exists', function () {
    Setting::set('scheduler_last_run', now()->toIso8601String());
    Setting::flushCache();

    $this->actingAs(schedAdmin())->get('/admin/templates')->assertOk()
        ->assertSee('Running')
        ->assertDontSee('Not running');
});

it('never prints the server path or the crontab command', function () {
    $response = $this->actingAs(schedAdmin())->get('/admin/templates')->assertOk();

    // The card used to publish both to anyone who could see the screen.
    $response->assertDontSee('schedule:run');
    $response->assertDontSee('/path/to');
    $response->assertDontSee('* * * * *');
    $response->assertDontSee('crontab');
});

it('keeps the page to admins', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get('/admin/templates')->assertStatus(403);
});
