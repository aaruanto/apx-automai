<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The confirmation header does not say "Done" next to the close button.
 *
 * The step counter sits immediately left of the modal's X. On success it was
 * set to "Done", so the header read "Done ✕" — which looks like a button you
 * are meant to press rather than a description of where you are, and the step
 * bar an inch below was already saying the same thing properly: all three
 * dots turn green on that same line.
 *
 * The counter is cleared instead. It has nothing left to count once the
 * booking is made.
 *
 * Verified end to end in a browser against a server with the mail driver set
 * to log: a real guest booking came back BK-0056, the label read "", all three
 * steps carried the done class, and the header was "Book a service | 1 Choose
 * service | 2 Your details | 3 Confirm".
 *
 * The bar has four steps now — 1 Vehicle, 2 Service, 3 Your details, 4
 * Confirm — because which services apply depends on the vehicle, so the
 * vehicle had to come first. Nothing about the fault this test guards changed
 * with it.
 */

it('clears the step counter instead of writing Done into it', function () {
    Service::create(['name' => 'Confirm Service', 'price' => 1100, 'duration' => 30, 'is_active' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain("document.getElementById('apx-step-label').textContent = '';");
    expect($html)->not->toContain("document.getElementById('apx-step-label').textContent = 'Done';");
});

it('still marks every step done on the bar that says it properly', function () {
    Service::create(['name' => 'Confirm Service 2', 'price' => 1100, 'duration' => 30, 'is_active' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    // This is what replaced the word, and it was already there — the label was
    // duplicating it badly rather than adding anything.
    //
    // Through STEP_IDS rather than a literal list. The wizard grew a fourth
    // step when the vehicle became step 1, and three separate places were
    // iterating their own hardcoded copy of the step ids — this assertion was
    // pinning one of them. The list is declared once now, so what this has to
    // check is that the success screen marks every step in it, not that it
    // names three particular ones.
    expect($html)->toContain("STEP_IDS.forEach(function(id){ document.getElementById(id).className='apx-step-item done'; });");
    expect($html)->toContain("var STEP_IDS = ['apx-s1', 'apx-s2', 'apx-s3', 'apx-s4'];");

    // And the done state is visible, not just a class name.
    expect($html)->toContain('.apx-step-item.done .apx-step-dot');
    expect($html)->toContain('.apx-step-item.done .apx-step-lbl');
});
