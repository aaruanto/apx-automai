<?php

use App\Models\User;
use App\Support\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The Service Due tab shows the email's HTML source, and now says so.
 *
 * The raw markup was never a data bug. These bodies go out through
 * Mail::html(), which wraps them in nothing, so each one is a complete
 * document — nested tables with inline styles, because that is what Outlook
 * and Gmail actually render. What was wrong was the editor: the appointment
 * body had a rich-text surface and a live preview beside it, while this one
 * was a bare textarea, and switchChannel() showed the preview card only on the
 * email tab. So the single tab whose editor displayed raw HTML was also the
 * only one with nothing to tell the admin what it would look like.
 *
 * Verified in a browser as well: both tabs render into the shared card, typing
 * updates it live, and Reset restores the shipped default.
 */

function sdAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('tells the admin the field holds HTML', function () {
    $html = $this->actingAs(sdAdmin())->get('/admin/templates')->assertOk()->getContent();

    expect($html)->toContain('Email Body (HTML)');
    expect($html)->toContain("the preview on the right is what the\n                                customer sees");
});

it('renders the preview for the service due tab, not just the email tab', function () {
    $html = $this->actingAs(sdAdmin())->get('/admin/templates')->assertOk()->getContent();

    // The card used to be hidden for every tab but 'email'.
    expect($html)->toContain("tab === 'email' || tab === 'maintenance'");
    expect($html)->toContain('function activeBodyMarkup');

    // And the textarea has to drive it.
    expect($html)->toMatch('/id="maintenanceBody"[^>]*oninput="updateEmailPreview\(\)"/s');
});

it('offers a reset that restores what the server would send', function () {
    $html = $this->actingAs(sdAdmin())->get('/admin/templates')->assertOk()->getContent();

    expect($html)->toContain('resetMaintenanceBody()');

    // The default is handed to the page rather than copied into it, so it
    // cannot drift from MessageTemplate.
    $default = MessageTemplate::defaults()['maintenance_body'];
    expect($default)->not->toBe('');
    expect($html)->toContain('const MAINTENANCE_DEFAULT_BODY = '.json_encode($default));
});

it('fills in the service due placeholders in the preview', function () {
    $html = $this->actingAs(sdAdmin())->get('/admin/templates')->assertOk()->getContent();

    // The sample set held only the appointment placeholders, so this tab's own
    // three stayed on screen as literal {due_date} in the preview whose whole
    // job is showing the customer's view.
    foreach (['{due_date}', '{reason}', '{recommendation}'] as $tag) {
        expect($html)->toContain("'".$tag."':");
    }
});

it('leaves the stored body as raw html', function () {
    // The storage format is load-bearing: every reminder email is sent from
    // it verbatim. Converting it for the editor's benefit would break them.
    $body = MessageTemplate::defaults()['maintenance_body'];

    expect($body)->toContain('<table');
    expect($body)->toContain('style=');
});

it('does not emit a blade directive into the raw script region', function () {
    $source = file_get_contents(resource_path('views/admin/templates/index.blade.php'));

    // Everything between the raw-region markers is passed through untouched,
    // so a directive written there — even inside a comment — reaches the
    // browser as text and is a syntax error that kills every function in the
    // block. That happened while writing this change.
    $start = strpos($source, '@'.'verbatim');
    $end   = strpos($source, '@end'.'verbatim');

    expect($start)->not->toBeFalse();
    expect($end)->toBeGreaterThan($start);

    $raw = substr($source, $start + 9, $end - $start - 9);

    expect($raw)->not->toContain('{!!');
    expect($raw)->not->toContain('@'.'json');
    expect($raw)->not->toContain('{{');
});
