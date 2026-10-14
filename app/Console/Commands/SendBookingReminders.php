<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Services\SmsService;
use App\Support\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send automated booking reminders to customers based on configured triggers';

    // Shared with the admin editor through MessageTemplate, which owns the
    // path. This command used to keep its own, pointing at a different file
    // from the one the panel saved to.
    private function loadTemplate(): array
    {
        return MessageTemplate::load();
    }

    private function fillPlaceholders(string $text, Booking $booking): string
    {
        $user     = $booking->user;
        $service  = $booking->service;
        $vehicle  = $booking->vehicle;
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
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    public function handle(SmsService $sms): void
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
                // A customer with no mobile number on file is skipped quietly:
                // that is a normal state, not a fault worth logging on every
                // run. SmsService never throws and writes its own sms_logs row
                // either way, so there is nothing to catch here.
                if (!empty($channels['send_sms']) && $sms->configured()) {
                    $phone = $user->phone ?? ($booking->user->customer->phone ?? null);

                    if ($phone) {
                        $result = $sms->send(
                            $phone,
                            $this->fillPlaceholders($template['sms_body'], $booking),
                            ['booking_id' => $booking->id, 'trigger' => $key]
                        );

                        if ($result['ok']) {
                            $sent++;
                        }
                    }
                }
            }
        }

        $this->info("Reminders sent: {$sent}");
        Log::info("reminders:send completed — {$sent} notifications sent.");
    }
}