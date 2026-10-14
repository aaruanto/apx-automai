<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use App\Support\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessageTemplateController extends Controller
{
    // ── Load saved template or defaults ────────────────────────────────────
    //
    // Both this and the reminders command go through MessageTemplate, which
    // owns the path. They used to each hold their own, and the two did not
    // match, so saving a template here changed nothing about the reminders
    // that actually went out.
    private function loadTemplate(): array
    {
        return MessageTemplate::load();
    }

    // ── Show template editor ───────────────────────────────────────────────
    public function index()
    {
        $template = $this->loadTemplate();
        $settings = $template; // pass everything to view

        // Real figures rather than the hard-coded "Running" the card used to show.
        $scheduler = \App\Support\SchedulerStatus::summary();

        return view('admin.templates.index', compact('settings', 'scheduler'));
    }

    // ── Save templates via AJAX ────────────────────────────────────────────
    public function save(Request $request)
    {
        $data = $request->validate([
            'email_subject'   => 'required|string|max:255',
            'email_preheader' => 'nullable|string|max:255',
            'email_body'      => 'required|string',
            'sms_body'        => 'required|string|max:480',
            'triggers'        => 'array',
            'channels'        => 'array',
            'sender_name'     => 'nullable|string|max:100',
            'reply_to'        => 'nullable|email',
        ]);

        MessageTemplate::save($data);

        return response()->json(['status' => 'saved']);
    }

    // ── Send test message ──────────────────────────────────────────────────
    //
    // The old version always flashed "Test message sent successfully", even
    // when the mailer was the log driver, when no address was given, or when
    // SMTP threw. A test button that cannot report failure is worse than no
    // button, so this one reports exactly what happened per channel and never
    // 500s the page.
    public function sendTest(Request $request, SmsService $sms)
    {
        $request->validate([
            'test_email'    => 'nullable|email',
            'test_phone'    => 'nullable|string',
            'template_type' => 'required|string',
        ]);

        $template = $this->loadTemplate();
        $results  = [];
        $ok       = true;

        // ── Email ──────────────────────────────────────────────────────────
        if (! $request->test_email) {
            $results[] = ['channel' => 'Email', 'ok' => null, 'message' => 'No address given — skipped.'];
        } elseif (empty($template['channels']['send_email'])) {
            $results[] = ['channel' => 'Email', 'ok' => null, 'message' => 'The email channel is switched off in this template.'];
        } elseif (config('mail.default') === 'log') {
            // The single most common reason "nothing arrives": mail is being
            // written to storage/logs instead of sent. Say so plainly.
            $ok        = false;
            $results[] = ['channel' => 'Email', 'ok' => false, 'message' => 'MAIL_MAILER is set to "log", so mail is written to the Laravel log instead of being sent. Set MAIL_MAILER=smtp to deliver for real.'];
        } else {
            try {
                $sample  = $this->sampleData();
                $subject = strtr($template['email_subject'], $sample);
                $body    = strtr($template['email_body'], $sample);

                Mail::html($body, function ($msg) use ($request, $subject, $template) {
                    $msg->to($request->test_email)
                        ->subject('[TEST] '.$subject)
                        ->from(config('mail.from.address'), $template['sender_name'] ?? 'APX AutoMai');

                    if (! empty($template['reply_to'])) {
                        $msg->replyTo($template['reply_to']);
                    }
                });

                $results[] = ['channel' => 'Email', 'ok' => true, 'message' => 'Sent to '.$request->test_email.' via '.config('mail.mailers.'.config('mail.default').'.host', config('mail.default')).'.'];
            } catch (\Throwable $e) {
                $ok        = false;
                $reason    = $this->scrubSecrets($e->getMessage());
                $results[] = ['channel' => 'Email', 'ok' => false, 'message' => $reason];
                Log::error('Test email failed: '.$reason);
            }
        }

        // ── SMS ────────────────────────────────────────────────────────────
        if (! $request->test_phone) {
            $results[] = ['channel' => 'SMS', 'ok' => null, 'message' => 'No number given — skipped.'];
        } elseif (empty($template['channels']['send_sms'])) {
            $results[] = ['channel' => 'SMS', 'ok' => null, 'message' => 'The SMS channel is switched off in this template.'];
        } elseif ($reason = $sms->unavailableReason()) {
            $ok        = false;
            $results[] = ['channel' => 'SMS', 'ok' => false, 'message' => $reason];
        } else {
            $body  = strtr($template['sms_body'], $this->sampleData());
            $parts = $sms->segments($body);
            $sent  = $sms->send($request->test_phone, $body, ['trigger' => 'test']);

            // Always report the segment count: a template that quietly became
            // three segments costs three times as much on every reminder, and
            // nothing else in the app would reveal that.
            $cost = sprintf(
                ' %d character%s, %s, %d segment%s.',
                $parts['length'], $parts['length'] === 1 ? '' : 's',
                $parts['encoding'],
                $parts['segments'], $parts['segments'] === 1 ? '' : 's'
            );

            $ok        = $ok && $sent['ok'];
            $results[] = [
                'channel' => 'SMS',
                'ok'      => $sent['ok'],
                'message' => $sent['message'].$cost,
            ];
        }

        if (! $results) {
            $results[] = ['channel' => 'Nothing', 'ok' => null, 'message' => 'Enter an email address or phone number first.'];
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'results' => $results]);
        }

        return back()->with($ok ? 'success' : 'error', collect($results)->map(
            fn ($r) => $r['channel'].': '.$r['message']
        )->implode(' '));
    }

    // ── Placeholder values used for a test send ────────────────────────────
    private function sampleData(): array
    {
        return [
            '{name}'       => 'Juan dela Cruz',
            '{first_name}' => 'Juan',
            '{service}'    => 'Full Car Wash',
            '{date}'       => now()->addDays(3)->format('F j, Y'),
            '{time}'       => '9:00 AM',
            '{plate}'      => 'ABC 1234',
            '{vehicle}'    => 'Toyota Vios 2021',
            '{reference}'  => '#BK-0042',
            '{branch}'     => 'APX Motors — Tandang Sora',
            '{contact}'    => '(02) 8123-4567',
            '{days_until}' => '3',
        ];
    }

    // ── Keep credentials out of anything we display or log ─────────────────
    //
    // SMTP transport exceptions quote the server conversation back at you. The
    // password should never appear there, but a mailer error is exactly the
    // wrong place to find out we were wrong about that.
    //
    // Only the password. The username is not a secret, and a 535 is almost
    // always caused by sending the wrong one — redacting it hides the one
    // field that identifies the mistake.
    private function scrubSecrets(string $message): string
    {
        $password = (string) config('mail.mailers.smtp.password');

        return $password === ''
            ? $message
            : str_replace($password, '[redacted]', $message);
    }
}
