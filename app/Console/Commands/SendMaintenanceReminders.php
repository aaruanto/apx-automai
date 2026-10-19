<?php

namespace App\Console\Commands;

use App\Models\MaintenanceRule;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Services\MaintenanceDue;
use App\Services\ReminderLedger;
use App\Services\SmsService;
use App\Support\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tell customers their vehicle is due for servicing.
 *
 * This is the other half of the notification requirement: not "you have an
 * appointment on Thursday" but "your car has not been in for six months".
 * Nothing prompts it except the passage of time, which is exactly why it has
 * to be scheduled rather than triggered by a customer action.
 *
 * One reminder per vehicle per run, for the most urgent rule that is due.
 * Sending one message per rule would mean three emails for a car that is
 * behind on three things, which reads as spam and buries the point.
 */
class SendMaintenanceReminders extends Command
{
    protected $signature = 'reminders:maintenance {--dry-run : List what would be sent without sending it}';

    protected $description = 'Notify customers whose vehicles are due for scheduled maintenance';

    public function handle(SmsService $sms, ReminderLedger $ledger, MaintenanceDue $due): int
    {
        $template = MessageTemplate::load();

        if (empty($template['triggers']['maintenance_due'])) {
            $this->info('Maintenance reminders are switched off in the message templates.');

            return self::SUCCESS;
        }

        $channels = $template['channels'] ?? [];
        $dryRun   = (bool) $this->option('dry-run');

        // Loaded once rather than per vehicle: the rule set is small and does
        // not change mid-run.
        $rules = MaintenanceRule::active()->with(['bundle', 'service'])->orderBy('sort_order')->get();

        if ($rules->isEmpty()) {
            $this->info('No active maintenance rules, nothing to check.');

            return self::SUCCESS;
        }

        $sent    = 0;
        $skipped = 0;

        // Guest records are included on purpose: someone the shop booked in on
        // their behalf still owns a car that needs servicing, and a reminder
        // is a reasonable way to bring them back. Deactivated accounts are not.
        $query = Vehicle::query()
            ->with(['user', 'user.customer'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('role', 'customer'));

        foreach ($query->lazyById(200) as $vehicle) {
            if (! $vehicle->user) {
                continue;
            }

            $dueNow = $due->forVehicle($vehicle, $rules);

            if ($dueNow->isEmpty()) {
                continue;
            }

            // Already coming in. The booking is the action the reminder was
            // asking for, so sending one now would be telling them something
            // they have already done.
            if ($due->hasUpcomingBooking($vehicle)) {
                continue;
            }

            // Most urgent first: anything overdue, then the earliest due date.
            $target = $dueNow
                ->sortBy(fn (array $d) => [$d['overdue'] ? 0 : 1, $d['due_date']->timestamp])
                ->first();

            [$s, $k] = $this->notify($vehicle, $target, $template, $channels, $sms, $ledger, $dryRun);
            $sent    += $s;
            $skipped += $k;
        }

        $this->info($dryRun
            ? "Would send: {$sent}. Already sent: {$skipped}."
            : "Maintenance reminders sent: {$sent}. Already sent, skipped: {$skipped}.");

        if ($sent > 0 && ! $dryRun) {
            Log::info("reminders:maintenance — {$sent} sent, {$skipped} skipped as duplicates.");
        }

        return self::SUCCESS;
    }

    /**
     * The de-duplication subject for a maintenance reminder.
     *
     * Keyed on the vehicle, the rule AND the due date, so the customer hears
     * about this service once per interval — not once ever, which would mean
     * never telling them again, and not once per run.
     */
    private function subjectFor(Vehicle $vehicle, array $target): string
    {
        return $vehicle->id.'@'.$target['rule']->id.'@'.$target['due_date']->toDateString();
    }

    /** @return array{0: int, 1: int} [sent, skipped] */
    private function notify(
        Vehicle $vehicle,
        array $target,
        array $template,
        array $channels,
        SmsService $sms,
        ReminderLedger $ledger,
        bool $dryRun,
    ): array {
        $user    = $vehicle->user;
        $subject = $this->subjectFor($vehicle, $target);
        $sent    = 0;
        $skip    = 0;

        if (! empty($channels['send_email']) && $user->email) {
            if ($ledger->alreadySent(Reminder::TYPE_MAINTENANCE, $subject, 'maintenance_due', 'email')) {
                $skip++;
            } elseif ($dryRun) {
                $this->line("  [email] {$vehicle->plate_number} to {$user->email} — {$target['rule']->name} due {$target['due_date']->toDateString()}");
                $sent++;
            } else {
                $claim = $ledger->claim($this->claimFor($vehicle, $target, 'email'));

                if (! $claim) {
                    $skip++;
                } else {
                    try {
                        $line = $this->fill($template['maintenance_subject'], $vehicle, $target);
                        $body = $this->fill($template['maintenance_body'], $vehicle, $target);

                        Mail::html($body, function ($msg) use ($user, $line, $template) {
                            $msg->to($user->email, $user->name)
                                ->subject($line)
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

        if (! empty($channels['send_sms']) && $sms->configured()) {
            $phone = $user->phone ?: ($user->customer->phone ?? null);

            if ($phone) {
                if ($ledger->alreadySent(Reminder::TYPE_MAINTENANCE, $subject, 'maintenance_due', 'sms')) {
                    $skip++;
                } elseif ($dryRun) {
                    $this->line("  [sms] {$vehicle->plate_number} to {$phone} — {$target['rule']->name}");
                    $sent++;
                } else {
                    $claim = $ledger->claim($this->claimFor($vehicle, $target, 'sms'));

                    if (! $claim) {
                        $skip++;
                    } else {
                        $result = $sms->send(
                            $phone,
                            $this->fill($template['maintenance_sms'], $vehicle, $target),
                            ['vehicle_id' => $vehicle->id, 'trigger' => 'maintenance_due'],
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

    private function claimFor(Vehicle $vehicle, array $target, string $channel): array
    {
        return [
            'type'                => Reminder::TYPE_MAINTENANCE,
            'subject'             => $this->subjectFor($vehicle, $target),
            'user_id'             => $vehicle->user_id,
            'vehicle_id'          => $vehicle->id,
            'maintenance_rule_id' => $target['rule']->id,
            'trigger_key'         => 'maintenance_due',
            'channel'             => $channel,
            'due_date'            => $target['due_date']->toDateString(),
        ];
    }

    private function fill(string $text, Vehicle $vehicle, array $target): string
    {
        $user = $vehicle->user;

        /** @var MaintenanceRule $rule */
        $rule = $target['rule'];

        $map = [
            '{name}'           => $user->name ?? 'Customer',
            '{first_name}'     => explode(' ', $user->name ?? 'Customer')[0],
            '{plate}'          => $vehicle->display_plate,
            // Collapsed, not just trimmed: a vehicle with no model on file
            // rendered as "Honda Click  2023" with a gap in the middle of the
            // customer's own subject line.
            '{vehicle}'        => $this->describe($vehicle),
            '{due_date}'       => Carbon::parse($target['due_date'])->format('F j, Y'),
            '{reason}'         => $target['reason'],
            '{recommendation}' => $rule->recommendation() ?? $rule->name,
            '{branch}'         => config('apx.branch_name', 'APX Motors — Tandang Sora'),
            '{contact}'        => config('apx.contact_number', ''),
            '{booking_url}'    => url('/'),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /** Make, model and year as one phrase, with no gaps where a field is blank. */
    private function describe(Vehicle $vehicle): string
    {
        return trim(preg_replace('/\s+/', ' ',
            ($vehicle->make ?? '').' '.($vehicle->model ?? '').' '.($vehicle->year ?? '')
        ));
    }
}
