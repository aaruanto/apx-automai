<?php

use App\Models\AuditLog;
use App\Models\MaintenanceRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The service intervals behind the "your vehicle is due" reminders.
 *
 * These decide who gets emailed and when, so the shop owns them rather than
 * the developer — and every edit is accountable.
 */
function ruleAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists the intervals for an admin', function () {
    MaintenanceRule::create([
        'name'            => 'Basic PMS due',
        'interval_months' => 6,
        'lead_days'       => 14,
        'is_active'       => true,
    ]);

    $this->actingAs(ruleAdmin())
        ->get('/admin/maintenance')
        ->assertOk()
        ->assertSee('Basic PMS due');
});

it('keeps the screen away from customers', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->get('/admin/maintenance')->assertForbidden();
});

// Separate test on purpose: actingAs persists for the rest of a test, so a
// signed-out assertion made after a signed-in one is still signed in.
it('keeps the screen behind a login', function () {
    $this->get('/admin/maintenance')->assertRedirect('/login');
});

it('creates an interval', function () {
    $this->actingAs(ruleAdmin())->post('/admin/maintenance', [
        'name'            => 'Brake fluid',
        'interval_months' => 24,
        'lead_days'       => 30,
    ])->assertRedirect();

    $rule = MaintenanceRule::where('name', 'Brake fluid')->sole();

    expect($rule->interval_months)->toBe(24);
    expect($rule->is_active)->toBeTrue();
});

it('refuses an interval that can never fall due', function () {
    // Neither months nor kilometres: the rule would sit in the list looking
    // configured and never produce a single reminder.
    $this->actingAs(ruleAdmin())->post('/admin/maintenance', [
        'name'      => 'Nothing at all',
        'lead_days' => 14,
    ])->assertSessionHasErrors('interval_months');

    expect(MaintenanceRule::count())->toBe(0);
});

it('accepts a distance-only interval', function () {
    $this->actingAs(ruleAdmin())->post('/admin/maintenance', [
        'name'        => 'Tyre rotation',
        'interval_km' => 10000,
        'lead_days'   => 0,
    ])->assertRedirect();

    expect(MaintenanceRule::where('name', 'Tyre rotation')->sole()->interval_km)->toBe(10000);
});

it('refuses two intervals with the same name', function () {
    MaintenanceRule::create([
        'name'            => 'Basic PMS due',
        'interval_months' => 6,
        'lead_days'       => 14,
        'is_active'       => true,
    ]);

    // The name is what the customer reads in the reminder and on their
    // dashboard, so duplicates are confusing rather than merely untidy.
    $this->actingAs(ruleAdmin())->post('/admin/maintenance', [
        'name'            => 'Basic PMS due',
        'interval_months' => 12,
        'lead_days'       => 14,
    ])->assertSessionHasErrors('name');
});

it('updates an interval and records what changed', function () {
    $rule = MaintenanceRule::create([
        'name'            => 'Basic PMS due',
        'interval_months' => 6,
        'lead_days'       => 14,
        'is_active'       => true,
    ]);

    $this->actingAs(ruleAdmin())->put("/admin/maintenance/{$rule->id}", [
        'name'            => 'Basic PMS due',
        'interval_months' => 9,
        'lead_days'       => 21,
    ])->assertRedirect();

    expect($rule->fresh()->interval_months)->toBe(9);
    expect($rule->fresh()->lead_days)->toBe(21);

    // An interval change alters who gets emailed and when, which is exactly
    // the kind of edit someone will later want to account for.
    $log = AuditLog::where('action', 'maintenance_rule.updated')->sole();
    expect($log->old_values['interval_months'])->toBe(6);
    expect($log->new_values['interval_months'])->toBe(9);
});

it('switches an interval off without deleting it', function () {
    $rule = MaintenanceRule::create([
        'name'            => 'Basic PMS due',
        'interval_months' => 6,
        'lead_days'       => 14,
        'is_active'       => true,
    ]);

    $this->actingAs(ruleAdmin())
        ->patch("/admin/maintenance/{$rule->id}/toggle")
        ->assertRedirect();

    // Reminders already sent reference the rule that raised them, so the row
    // has to survive being switched off.
    expect($rule->fresh())->not->toBeNull();
    expect($rule->fresh()->is_active)->toBeFalse();

    $this->actingAs(ruleAdmin())->patch("/admin/maintenance/{$rule->id}/toggle");
    expect($rule->fresh()->is_active)->toBeTrue();
});

it('rejects a lead time longer than half a year', function () {
    $this->actingAs(ruleAdmin())->post('/admin/maintenance', [
        'name'            => 'Far too early',
        'interval_months' => 6,
        'lead_days'       => 400,
    ])->assertSessionHasErrors('lead_days');
});
