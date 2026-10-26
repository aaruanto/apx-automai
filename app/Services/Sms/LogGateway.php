<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Everything a real send does, except contacting anyone.
 *
 * The message is still folded to GSM-7, the number is still normalised, the
 * segments are still counted and the sms_logs row is still written — all of
 * that happens in SmsService before a gateway is reached. This one simply
 * does not make the HTTP call, so nothing is billed and no handset rings.
 *
 * It exists because the expensive question during development is "did the
 * right reminder fire, for the right booking, exactly once?", and the answer
 * lives in sms_logs and the reminders table, not on a phone. Chasing that
 * bug costs a credit per attempt on a real provider and nothing here, which
 * matters when the account has twenty-eight of them.
 *
 * What it cannot tell you: whether the key is right, whether the provider
 * accepts the message, or whether the carrier delivers it. Those need a real
 * send. Use this for the logic and spend credits proving delivery.
 */
class LogGateway implements SmsGateway
{
    public function name(): string
    {
        return 'log';
    }

    /** Always ready: there is nothing to configure and nothing to be down. */
    public function configured(): bool
    {
        return true;
    }

    public function unavailableReason(): ?string
    {
        return null;
    }

    /**
     * Refuses nothing.
     *
     * Deliberately not mirroring Semaphore's "TEST" rule. A simulated send
     * that rejects more than the real one would send you hunting a problem
     * that only exists in the simulator.
     */
    public function refuses(string $content): ?array
    {
        return null;
    }

    public function dispatch(string $recipient, string $content): array
    {
        // Written to the application log as well as sms_logs, so a message is
        // visible while tailing a run without opening the database.
        Log::info('SMS simulated (SMS_DRIVER=log)', [
            'recipient' => $recipient,
            'content'   => $content,
        ]);

        return [
            'ok'        => true,
            // Not 'queued' or 'sent'. Anyone reading sms_logs should be able
            // to tell at a glance that nothing left the building.
            'status'    => 'simulated',
            'reference' => 'sim-'.Str::lower(Str::random(12)),
            'message'   => 'Simulated — SMS_DRIVER is set to log, so nothing was sent and no credit was used.',
        ];
    }

    /** Nothing is spent, so there is no balance to report. */
    public function credits(): ?int
    {
        return null;
    }
}
