<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Reminder;
use App\Services\ReminderLedger;
use App\Services\SmsService;
use App\Support\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Remind customers about appointments they have already made.
 *
 * Every send goes through ReminderLedger first. Without it this command had no
 * memory at all: its day windows match on whereDate(), which stays true for
 * the whole of that day, so one booking seven days out was mailed on every
 * scheduler tick — about 1,440 identical emails a day against a 300/day
 * sending limit. The scheduler never actually running on Render was the only
 * thing hiding it.
 */
class SendBookingReminders extends Command
{
    protected $signature = 'reminders:send {--dry-run : List what would be sent without sending it}';

    protected $description = 'Send appointment reminders for upcoming bookings';

    /**
     * The day windows, and how far ahead each one looks.
     *
     * The 2h window is handled separately because it is time-of-day sensitive:
     * it needs the scheduler ticking at least every 15 minutes to catch
     * anything, and ships switched off for that reason. The day windows work
     * on any schedule.
     */
    private const WINDOWS = [
        'remind_7d' => 7,
        'remind_3d' => 3,
        'remind_1d' => 1,
    ];

    public function handle(SmsService $sms, ReminderLedger $ledger): int
    {
        $template = MessageTemplate::load();
        $triggers = $template['triggers'] ?? [];
        $channels = $template['channels'] ?? [];
        $dryRun   = (bool) $this->option('dry-run');

        $sent    = 0;
        $skipped = 0;

        foreach (self::WINDOWS as $key => $days) {
            if (empty($triggers[$key])) {
                continue;
            }

            foreach ($this->bookingsOn(Carbon::today()->addDays($days)) as $booking) {
                [$s, $k] = $this->remind($booking, $key, $template, $channels, $sms, $ledger, $dryRun);
                $sent    += $s;
                $skipped += $k;
            }
        }

        if (! empty($triggers['remind_2h'])) {
            foreach ($this->bookingsInTwoHours() as $booking) {
                [$s, $k] = $this->remind($booking, 'remind_2h', $template, $channels, $sms, $ledger, $dryRun);
                $sent    += $s;
                $skipped += $k;
            }
        }

        $this->info($dryRun
            ? "Would send: {$sent}. Already sent: {$skipped}."
            : "Reminders sent: {$sent}. Already sent, skipped: {$skipped}.");

        if ($sent > 0 && ! $dryRun) {
            Log::info("reminders:send — {$sent} sent, {$skipped} skipped as duplicates.");
        }

        return self::SUCCESS;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Booking> */
    private function bookingsOn(Carbon $date)
    {
        return Booking::with(['user', 'user.customer', 'service', 'vehicle'])
            ->whereDate('booking_date', $date->toDateString())
            ->where('status', 'confirmed')
            ->get();
    }

    /**
     * Bookings starting roughly two hours from now.
     *
     * A 15-minute band rather than an exact match, so the reminder still goes
     * out when the scheduler ticks a few minutes either side. Duplicate sends
     * inside the band are the ledger's problem, not this query's — which is
     * what lets the band be generous.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Booking>
     */
    private function bookingsInTwoHours()
    {
        $from = Carbon::now()->addHours(2);
        $to   = $from->copy()->addMinutes(15);

        return Booking::with(['user', 'user.customer', 'service', 'vehicle'])
            ->whereDate('booking_date', Carbon::today()->toDateString())
            ->whereTime('booking_time', '>=', $from->format('H:i:00'))
            ->whereTime('booking_time', '<=', $to->format('H:i:00'))
            ->where('status', 'confirmed')
            ->get();
    }

    /** @return array{0: int, 1: int} [sent, skipped] */
    private function remind(
        Booking $booking,
        string $key,
        array $template,
        array $channels,
        SmsService $sms,
        ReminderLedger $ledger,
        bool $dryRun,
    ): array {
        $user = $booking->user;

        if (! $user) {
            return [0, 0];
        }

        $sent = 0;
        $skip = 0;

        // ── Email ─────────────────────────────────────────────────────────
        if (! empty($channels['send_email']) && $user->email) {
            if ($ledger->alreadySent(Reminder::TYPE_APPOINTMENT, $booking->id, $key, 'email')) {
                $skip++;
            } elseif ($dryRun) {
                $this->line("  [email] {$key} to {$user->email} (booking #{$booking->id})");
                $sent++;
            } else {
                $claim = $ledger->claim($this->claimFor($booking, $key, 'email'));

                if (! $claim) {
                    $skip++;
                } else {
                    try {
                        $subject = $this->fill($template['email_subject'], $booking);
                        $body    = $this->fill($template['email_body'], $booking);

                        Mail::html($body, function ($msg) use ($user, $subject, $template) {
                            $msg->to($user->email, $user->name)
                                ->subject($subject)
                                ->from(config('mail.from.address'), $template['sender_name'] ?? 'APX AutoMai');

                            if (! empty($template['reply_to'])) {
                                $msg->replyTo($template['reply_to']);
                            }
                        });

                        $ledger->markSent($claim);
                        $sent++;
                    } catch (\Throwable $e) {
                        $ledger->release($claim, $e->getMessage());
                    }
                }
            }
        }

        // ── SMS ───────────────────────────────────────────────────────────
        // A customer with no mobile number on file is skipped quietly: that is
        // a normal state, not a fault worth logging on every run.
        if (! empty($channels['send_sms']) && $sms->configured()) {
            $phone = $user->phone ?: ($user->customer->phone ?? null);

            if ($phone) {
                if ($ledger->alreadySent(Reminder::TYPE_APPOINTMENT, $booking->id, $key, 'sms')) {
                    $skip++;
                } elseif ($dryRun) {
                    $this->line("  [sms] {$key} to {$phone} (booking #{$booking->id})");
                    $sent++;
                } else {
                    $claim = $ledger->claim($this->claimFor($booking, $key, 'sms'));

                    if (! $claim) {
                        $skip++;
                    } else {
                        // SmsService never throws and writes its own sms_logs
                        // row either way, so the result is the only signal.
                        $result = $sms->send(
                            $phone,
                            $this->fill($template['sms_body'], $booking),
                            ['booking_id' => $booking->id, 'trigger' => $key],
                        );

                        if ($result['ok']) {
                            $ledger->markSent($claim);
                            $sent++;
                        } else {
                            $ledger->release($claim, $result['message'] ?? 'SMS send failed');
                        }
                    }
                }
            }
        }

        return [$sent, $skip];
    }

    private function claimFor(Booking $booking, string $key, string $channel): array
    {
        return [
            'type'        => Reminder::TYPE_APPOINTMENT,
            'subject'     => $booking->id,
            'user_id'     => $booking->user_id,
            'vehicle_id'  => $booking->vehicle_id,
            'booking_id'  => $booking->id,
            'trigger_key' => $key,
            'channel'     => $channel,
            'due_date'    => $booking->booking_date,
        ];
    }

    private function fill(string $text, Booking $booking): string
    {
        $user    = $booking->user;
        $service = $booking->service;
        $vehicle = $booking->vehicle;

        $map = [
            '{name}'       => $user->name ?? 'Customer',
            '{first_name}' => explode(' ', $user->name ?? 'Customer')[0],
            '{service}'    => $service->name ?? 'service',
            '{date}'       => Carbon::parse($booking->booking_date)->format('F j, Y'),
            '{time}'       => Carbon::parse($booking->booking_time)->format('g:i A'),
            '{plate}'      => $vehicle->plate_number ?? '-',
            // Collapsed, not just trimmed: a vehicle with no model on file
            // rendered as "Honda Click  2023", with a gap in the middle of
            // the customer's own reminder.
            '{vehicle}'    => trim(preg_replace('/\s+/', ' ',
                ($vehicle->make ?? '').' '.($vehicle->model ?? '').' '.($vehicle->year ?? '')
            )),
            '{reference}'  => $booking->reference_number ?? '#BK-'.str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
            '{branch}'     => config('apx.branch_name', 'APX Motors — Tandang Sora'),
            '{contact}'    => config('apx.contact_number', ''),
            '{days_until}' => (string) Carbon::today()->diffInDays(Carbon::parse($booking->booking_date)),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }
}
