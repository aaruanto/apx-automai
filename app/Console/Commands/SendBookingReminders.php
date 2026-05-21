<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendBookingReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send automated booking reminders to customers based on configured triggers';

    private function loadTemplate(): array
    {
        $path = 'templates/reminder.json';

        if (Storage::exists($path)) {
            return json_decode(Storage::get($path), true);
        }

        return [
            'email_subject'   => 'Reminder: Your {service} appointment is on {date}',
            'email_body'      => '<p>Hi {name}, your {service} is on {date} at {time}.</p>',
            'sms_body'        => 'Hi {name}! Reminder: {service} on {date} at {time}. Ref: {reference}.',
            'triggers'        => ['remind_7d'=>true,'remind_3d'=>true,'remind_1d'=>true,'remind_2h'=>false],
            'channels'        => ['send_email'=>true,'send_sms'=>true],
            'sender_name'     => 'APX AutoMai',
            'reply_to'        => '',
        ];
    }

    private function fillPlaceholders(string $text, Booking $booking): string
    {
        $user     = $booking->user;
        $service  = $booking->service;
        $vehicle  = $booking->vehicle;
        $customer = $user->customer ?? null;

        $map = [
            '{name}'         => $user->name ?? 'Customer',
            '{first_name}'   => explode(' ', $user->name ?? 'Customer')[0],
            '{service}'      => $service->name ?? 'service',
            '{date}'         => Carbon::parse($booking->booking_date)->format('F j, Y'),
            '{time}'         => Carbon::parse($booking->booking_time)->format('g:i A'),
            '{plate}'        => $vehicle->plate_number ?? '—',
            '{vehicle}'      => trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '') . ' ' . ($vehicle->year ?? '')),
            '{reference}'    => $booking->reference_number ?? '#BK-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT),
            '{branch}'       => config('apx.branch_name', 'APX Motors — Tandang Sora'),
            '{contact}'      => config('apx.contact_number', ''),
            '{days_until}'   => (string) Carbon::today()->diffInDays(Carbon::parse($booking->booking_date)),
            '{loyalty_tier}' => ucfirst($customer->tier ?? 'bronze'),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    public function handle(): void
    {
        $template = $this->loadTemplate();
        $triggers = $template['triggers'] ?? [];
        $channels = $template['channels'] ?? [];
        $sent     = 0;

        // Define which trigger windows to check
        $windows = [
            'remind_7d' => [7,  0],   // exactly 7 days away
            'remind_3d' => [3,  0],   // exactly 3 days away
            'remind_1d' => [1,  0],   // exactly 1 day away
            'remind_2h' => [0,  2],   // same day, 2 hours away (hours check)
        ];

        foreach ($windows as $key => [$days, $hours]) {
            if (empty($triggers[$key])) continue;

            // Build date window
            if ($days > 0) {
                $targetDate = Carbon::today()->addDays($days)->toDateString();
                $bookings = Booking::with(['user', 'service', 'vehicle', 'user.customer'])
                    ->whereDate('booking_date', $targetDate)
                    ->where('status', 'confirmed')
                    ->get();
            } else {
                // Same-day, time-based check (2h window)
                $targetTime = Carbon::now()->addHours($hours);
                $bookings = Booking::with(['user', 'service', 'vehicle', 'user.customer'])
                    ->whereDate('booking_date', Carbon::today())
                    ->whereTime('booking_time', '>=', $targetTime->format('H:i:00'))
                    ->whereTime('booking_time', '<=', $targetTime->addMinutes(15)->format('H:i:00'))
                    ->where('status', 'confirmed')
                    ->get();
            }

            foreach ($bookings as $booking) {
                $user = $booking->user;
                if (!$user) continue;

                // ── Email ──────────────────────────────────────────────────
                if (!empty($channels['send_email']) && $user->email) {
                    try {
                        $subject = $this->fillPlaceholders($template['email_subject'], $booking);
                        $body    = $this->fillPlaceholders($template['email_body'], $booking);

                        Mail::html($body, function ($msg) use ($user, $subject, $template) {
                            $msg->to($user->email, $user->name)
                                ->subject($subject)
                                ->from(config('mail.from.address'), $template['sender_name'] ?? 'APX AutoMai');
                            if (!empty($template['reply_to'])) {
                                $msg->replyTo($template['reply_to']);
                            }
                        });

                        $sent++;
                        Log::info("Reminder email sent [{$key}] to {$user->email} for booking #{$booking->id}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send reminder email to {$user->email}: " . $e->getMessage());
                    }
                }

                // ── SMS ────────────────────────────────────────────────────
                if (!empty($channels['send_sms'])) {
                    $phone = $user->phone ?? ($booking->user->customer->phone ?? null);

                    if ($phone) {
                        try {
                            $smsBody = $this->fillPlaceholders($template['sms_body'], $booking);

                            // TODO: Replace with your SMS gateway call
                            // Example (Semaphore):
                            // \Http::post('https://api.semaphore.co/api/v4/messages', [
                            //     'apikey'     => config('services.semaphore.key'),
                            //     'number'     => $phone,
                            //     'message'    => $smsBody,
                            //     'sendername' => $template['sender_name'] ?? 'APX',
                            // ]);

                            Log::info("Reminder SMS [{$key}] queued to {$phone} for booking #{$booking->id}: {$smsBody}");
                            $sent++;
                        } catch (\Exception $e) {
                            Log::error("Failed to send reminder SMS to {$phone}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info("Reminders sent: {$sent}");
        Log::info("reminders:send completed — {$sent} notifications sent.");
    }
}