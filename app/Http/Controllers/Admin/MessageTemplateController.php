<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MessageTemplateController extends Controller
{
    private string $templatePath = 'private/templates/reminder.json';

    // ── Load saved template or defaults ────────────────────────────────────
    private function loadTemplate(): array
    {
        if (Storage::exists($this->templatePath)) {
            return json_decode(Storage::get($this->templatePath), true);
        }

        return [
            'email_subject'   => 'Reminder: Your {service} appointment is on {date}',
            'email_preheader' => 'Hi {name}, just a reminder about your upcoming {service}.',
            'email_body'      => '',
            'sms_body'        => 'Hi {name}! Reminder from APX AutoMai. Your {service} is on {date} at {time}. Ref: {reference}.',
            'triggers' => [
                'remind_7d' => true,
                'remind_3d' => true,
                'remind_1d' => true,
                'remind_2h' => false,
            ],
            'channels' => [
                'send_email' => true,
                'send_sms'   => true,
            ],
            'sender_name' => 'APX AutoMai',
            'reply_to'    => '',
        ];
    }

    // ── Show template editor ───────────────────────────────────────────────
    public function index()
    {
        $template = $this->loadTemplate();
        $settings = $template; // pass everything to view

        return view('admin.templates.index', compact('settings'));
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

        Storage::put($this->templatePath, json_encode($data, JSON_PRETTY_PRINT));

        return response()->json(['status' => 'saved']);
    }

    // ── Send test message ──────────────────────────────────────────────────
    public function sendTest(Request $request)
    {
        $request->validate([
            'test_email'    => 'nullable|email',
            'test_phone'    => 'nullable|string',
            'template_type' => 'required|string',
        ]);

        $template = $this->loadTemplate();

        // Sample data for test send
        $sample = [
            '{name}'         => 'Juan dela Cruz',
            '{first_name}'   => 'Juan',
            '{service}'      => 'Full Car Wash',
            '{date}'         => now()->addDays(3)->format('F j, Y'),
            '{time}'         => '9:00 AM',
            '{plate}'        => 'ABC 1234',
            '{vehicle}'      => 'Toyota Vios 2021',
            '{reference}'    => '#BK-0042',
            '{branch}'       => 'APX Motors — Tandang Sora',
            '{contact}'      => '(02) 8123-4567',
            '{days_until}'   => '3',
            '{loyalty_tier}' => 'Gold',
        ];

        $emailBody    = str_replace(array_keys($sample), array_values($sample), $template['email_body']);
        $emailSubject = str_replace(array_keys($sample), array_values($sample), $template['email_subject']);
        $smsBody      = str_replace(array_keys($sample), array_values($sample), $template['sms_body']);

        // Send test email
        if ($request->test_email && $template['channels']['send_email']) {
            Mail::html($emailBody, function ($msg) use ($request, $emailSubject, $template) {
                $msg->to($request->test_email)
                    ->subject('[TEST] ' . $emailSubject)
                    ->from(config('mail.from.address'), $template['sender_name'] ?? 'APX AutoMai');
                if ($template['reply_to']) {
                    $msg->replyTo($template['reply_to']);
                }
            });
        }

        // Send test SMS
        // TODO: Integrate your SMS gateway here (e.g. Semaphore, Vonage, Twilio)
        // Example with Semaphore:
        // if ($request->test_phone && $template['channels']['send_sms']) {
        //     Http::post('https://api.semaphore.co/api/v4/messages', [
        //         'apikey'      => config('services.semaphore.key'),
        //         'number'      => $request->test_phone,
        //         'message'     => $smsBody,
        //         'sendername'  => $template['sender_name'] ?? 'APX',
        //     ]);
        // }

        return back()->with('success', 'Test message sent successfully.');
    }
}