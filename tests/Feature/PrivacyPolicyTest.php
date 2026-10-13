<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the privacy policy publicly', function () {
    $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
});

it('covers every section the Data Privacy Act expects', function (string $heading) {
    $this->get('/privacy')->assertOk()->assertSee($heading, false);
})->with([
    'What We Collect',
    'Why We Collect It',
    'Who Can See It',
    'How It Is Stored and Protected',
    'How Long We Keep It',
    'Your Rights Under the Data Privacy Act',
    'Republic Act No. 10173',
    'Contact Us',
]);

it('marks the passages the team still has to write', function () {
    // The brief asked for these to be unmissable rather than quietly blank.
    $this->get('/privacy')->assertOk()->assertSee('[TO CONFIRM]', false);
});

it('is linked from login and register', function () {
    $this->get('/login')->assertOk()->assertSee(route('privacy'), false);
    $this->get('/register')->assertOk()->assertSee(route('privacy'), false);
});

it('is linked from both dashboards', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $this->actingAs($customer)->get('/customer/dashboard')->assertOk()
        ->assertSee(route('privacy'), false);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
        ->assertSee(route('privacy'), false);
});

it('no longer shows the LEGAL eyebrow on the terms page', function () {
    $this->get('/terms')->assertOk()->assertDontSee('// Legal //', false);
});
