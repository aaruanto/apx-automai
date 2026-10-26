<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Sending a text, independent of who carries it.
 *
 * This owns everything true of any provider: folding the message to GSM-7,
 * normalising the number, counting segments, refusing anything absurdly
 * long, writing the sms_logs row, and returning the ok/failed shape the
 * reminder commands and the admin test send already read. The provider
 * owns only the HTTP call and how to read the answer — see App\Services\Sms.
 *
 * SMS_DRIVER picks one. That is deliberate rather than tidy: a Philippine
 * carrier must approve an alphanumeric sender name before a branded message
 * can be delivered, which takes weeks and paperwork from a third party. With
 * the provider behind a config value, work does not stop while that is in
 * review — log sends nothing and costs nothing, and a provider with an
 * unregistered default sender can carry real messages in the meantime.
 *
 * Every attempt is written to sms_logs whatever the outcome, because the
 * question afterwards is always "did the customer actually get a text", and
 * the gateway's own dashboard is not where someone running this shop will
 * think to look.
 */
class SmsService
{
    public function __construct(private SmsGateway $gateway) {}

    /** Which provider is carrying messages: 'log', 'unisms', 'semaphore'. */
    public function driver(): string
    {
        return $this->gateway->name();
    }

    /**
     * Semaphore splits anything over 160 characters itself and bills per part,
     * with no documented ceiling. This limit is ours: without it a runaway
     * template could quietly cost dozens of credits per send.
     */
    public const MAX_LENGTH = 1600;

    /**
     * Characters representable in the GSM 03.38 7-bit alphabet. Anything
     * outside this forces the whole message into UCS-2, which more than halves
     * the per-segment allowance — a single smart quote pasted in from Word can
     * double the cost of every reminder, so it is worth counting exactly.
     */
    private const GSM7 = '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡'
        .'ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà'
        ."\n\r";

    /** GSM-7 characters that cost two septets each. */
    private const GSM7_EXTENDED = ['^', '{', '}', '\\', '[', '~', ']', '|', '€'];

    // ── Configuration ──────────────────────────────────────────────────────

    /** Whatever the chosen provider needs in order to send. */
    public function configured(): bool
    {
        return $this->gateway->configured();
    }

    /** Why SMS is unavailable, phrased for an admin to act on. Null when fine. */
    public function unavailableReason(): ?string
    {
        return $this->gateway->unavailableReason();
    }

    // ── Philippine number normalisation ────────────────────────────────────

    /**
     * Reduce any of the shapes customers actually type to E.164:
     *
     *   09171234567    ->  +639171234567
     *   9171234567     ->  +639171234567
     *   639171234567   ->  +639171234567
     *   +639171234567  ->  +639171234567
     *
     * Spaces, dashes, dots and brackets are ignored throughout. Returns null
     * for anything that is not a Philippine mobile number, so a landline or a
     * typo is skipped rather than sent and silently billed.
     */
    public function normalise(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        // Strip whichever prefix is present, leaving the 10-digit subscriber
        // number, which for a PH mobile always begins with 9.
        $national = match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '63') => substr($digits, 2),
            strlen($digits) === 11 && str_starts_with($digits, '0')  => substr($digits, 1),
            strlen($digits) === 10                                   => $digits,
            default                                                  => null,
        };

        if ($national === null || preg_match('/^9\d{9}$/', $national) !== 1) {
            return null;
        }

        return '+63'.$national;
    }

    // ── Segment counting ───────────────────────────────────────────────────

    /**
     * How many segments a message bills as, and in which encoding.
     *
     * GSM-7 fits 160 characters on its own, but only 153 per part once a
     * message splits, because the concatenation header eats into every part.
     * UCS-2 is 70 and 67 respectively.
     */
    /**
     * Swap typographic characters for their GSM-7 equivalents.
     *
     * A single character outside the GSM-7 alphabet forces the whole message
     * into UCS-2, which cuts a segment from 160 characters to 70. The branch
     * name in config carries an em-dash, so every service-due text was being
     * billed as three segments instead of one — triple cost, invisibly, on
     * every send. A peso sign does the same, and a shop writing prices into a
     * template would never guess why.
     *
     * Only characters that come from our own config and templates are mapped.
     * Anything else is left exactly as written: a customer's name is worth
     * more than a segment, so a message that genuinely needs Unicode still
     * gets it.
     */
    public function gsmSafe(string $content): string
    {
        return strtr($content, [
            '—' => '-',   // em-dash
            '–' => '-',   // en-dash
            '‑' => '-',   // non-breaking hyphen
            '₱' => 'P',   // peso — "P1,350" is how a receipt would write it
            '’' => "'",
            '‘' => "'",
            '“' => '"',
            '”' => '"',
            '…' => '...',
            '•' => '*',
            '·' => '.',
            "\u{00A0}" => ' ', // non-breaking space
        ]);
    }

    public function segments(string $content): array
    {
        $unicode = ! $this->isGsm7($content);

        if ($unicode) {
            $length = mb_strlen($content, 'UTF-8');
            $single = 70;
            $multi  = 67;
        } else {
            $length = $this->gsm7Length($content);
            $single = 160;
            $multi  = 153;
        }

        return [
            'encoding' => $unicode ? 'UCS-2' : 'GSM-7',
            'length'   => $length,
            'segments' => $length <= $single ? 1 : max(1, (int) ceil($length / $multi)),
            'per_part' => $length <= $single ? $single : $multi,
        ];
    }

    private function isGsm7(string $content): bool
    {
        foreach ($this->characters($content) as $char) {
            if (in_array($char, self::GSM7_EXTENDED, true)) {
                continue;
            }

            if (mb_strpos(self::GSM7, $char, 0, 'UTF-8') === false) {
                return false;
            }
        }

        return true;
    }

    /** GSM-7 length, counting the extended characters as the two they cost. */
    private function gsm7Length(string $content): int
    {
        $length = 0;

        foreach ($this->characters($content) as $char) {
            $length += in_array($char, self::GSM7_EXTENDED, true) ? 2 : 1;
        }

        return $length;
    }

    /** @return array<int, string> */
    private function characters(string $content): array
    {
        return preg_split('//u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    // ── Sending ────────────────────────────────────────────────────────────

    /**
     * Send one message. Never throws: the callers are booking flows and
     * scheduled commands, and neither should fail because a gateway is down.
     * The returned array always has ok, message and log.
     */
    public function send(?string $to, string $content, array $context = []): array
    {
        if ($reason = $this->unavailableReason()) {
            return $this->fail($reason, null, $content, $context, 'not_configured');
        }

        // Done before anything measures or logs the message, so the length
        // check, the stored sms_logs row and what the provider bills for are
        // all the same text.
        $content = $this->gsmSafe($content);

        $recipient = $this->normalise($to);

        if ($recipient === null) {
            return $this->fail(
                'Not a Philippine mobile number, so nothing was sent.',
                $to, $content, $context, 'invalid_number'
            );
        }

        if (mb_strlen($content) > self::MAX_LENGTH) {
            return $this->fail(
                'Message is '.mb_strlen($content).' characters; this app refuses anything over '.self::MAX_LENGTH.'.',
                $recipient, $content, $context, 'too_long'
            );
        }

        // Provider-specific content rules, such as Semaphore discarding
        // anything that begins with "TEST" while reporting success.
        if ($refusal = $this->gateway->refuses($content)) {
            return $this->fail(
                $refusal['reason'], $recipient, $content, $context, $refusal['status']
            );
        }

        // The only line that depends on who is carrying the message. It never
        // throws: a provider being unreachable is an outcome, and an sms_logs
        // row is written either way.
        $result = $this->gateway->dispatch($recipient, $content);

        if (! $result['ok']) {
            return $this->fail(
                $result['message'], $recipient, $content, $context, $result['status']
            );
        }

        $log = $this->log($context + [
            'recipient'    => $recipient,
            'content'      => $content,
            'status'       => $result['status'],
            'reference_id' => $result['reference'],
            'segments'     => $this->segments($content)['segments'],
        ]);

        return [
            'ok'      => true,
            'message' => $result['message'],
            'log'     => $log,
        ];
    }

    /**
     * Remaining credits, or null when the provider cannot say — which is
     * also the honest answer when nothing is being spent, as under the log
     * driver. The admin screen shows nothing rather than a misleading zero.
     */
    public function credits(): ?int
    {
        return $this->gateway->credits();
    }

    // ── Logging ────────────────────────────────────────────────────────────

    private function fail(string $reason, ?string $to, string $content, array $context, string $status): array
    {
        $log = $this->log($context + [
            'recipient'   => $to,
            'content'     => $content,
            'status'      => $status,
            'fail_reason' => $reason,
            'segments'    => $this->segments($content)['segments'],
        ]);

        Log::warning('SMS not sent ['.$status.']: '.$reason);

        return ['ok' => false, 'message' => $reason, 'log' => $log];
    }

    private function log(array $attributes): ?SmsLog
    {
        try {
            return SmsLog::create($attributes);
        } catch (\Throwable $e) {
            // A logging failure must never become a send failure.
            Log::error('Could not write sms_logs row: '.$e->getMessage());

            return null;
        }
    }
}
