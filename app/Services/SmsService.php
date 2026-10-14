<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UniSMS gateway.
 *
 * POST {base}/sms with HTTP Basic auth — the secret key as the username and an
 * empty password — and a body of recipient, content and sender_id. Success is
 * 201 with the created message under a "message" key; 401, 422 and 429 are the
 * documented failures.
 *
 * Every attempt is written to sms_logs whatever the outcome, because the
 * question afterwards is always "did the customer actually get a text", and
 * the gateway's own dashboard is not where someone running this shop will
 * think to look.
 */
class SmsService
{
    /** Longest message UniSMS accepts. */
    public const MAX_LENGTH = 670;

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

    public function configured(): bool
    {
        return filled(config('services.unisms.key'))
            && filled(config('services.unisms.sender_id'));
    }

    /** Why SMS is unavailable, phrased for an admin to act on. Null when fine. */
    public function unavailableReason(): ?string
    {
        if (blank(config('services.unisms.key'))) {
            return 'UNISMS_KEY is not set, so SMS is switched off.';
        }

        if (blank(config('services.unisms.sender_id'))) {
            return 'UNISMS_SENDER_ID is not set. UniSMS requires a sender ID on every message.';
        }

        return null;
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

        $recipient = $this->normalise($to);

        if ($recipient === null) {
            return $this->fail(
                'Not a Philippine mobile number, so nothing was sent.',
                $to, $content, $context, 'invalid_number'
            );
        }

        if (mb_strlen($content) > self::MAX_LENGTH) {
            return $this->fail(
                'Message is '.mb_strlen($content).' characters; UniSMS accepts at most '.self::MAX_LENGTH.'.',
                $recipient, $content, $context, 'too_long'
            );
        }

        try {
            $response = Http::withBasicAuth((string) config('services.unisms.key'), '')
                ->timeout((int) config('services.unisms.timeout'))
                ->acceptJson()
                ->post($this->endpoint('sms'), [
                    'recipient' => $recipient,
                    'content'   => $content,
                    'sender_id' => (string) config('services.unisms.sender_id'),
                ]);
        } catch (\Throwable $e) {
            return $this->fail(
                'Could not reach UniSMS: '.$e->getMessage(),
                $recipient, $content, $context, 'transport_error'
            );
        }

        if (! $response->successful()) {
            return $this->fail(
                $this->describeFailure($response),
                $recipient, $content, $context, 'http_'.$response->status()
            );
        }

        $message   = $response->json('message') ?: [];
        $reference = $message['reference_id'] ?? null;

        $log = $this->log($context + [
            'recipient'    => $recipient,
            'content'      => $content,
            'status'       => $message['status'] ?? 'pending',
            'reference_id' => $reference,
            'segments'     => $this->segments($content)['segments'],
        ]);

        return [
            'ok'      => true,
            'message' => 'Queued with UniSMS'.($reference ? ' (ref '.$reference.')' : '').'.',
            'log'     => $log,
        ];
    }

    /**
     * Turn a failed response into something an admin can act on. UniSMS
     * returns a body on 422; the rest are better explained by what they mean
     * for this app than by the bare status line.
     */
    private function describeFailure(Response $response): string
    {
        $detail = $response->json('message') ?? $response->json('error');
        $detail = is_array($detail) ? json_encode($detail) : $detail;
        $detail = is_string($detail) ? trim($detail) : null;

        return match ($response->status()) {
            401 => 'UniSMS rejected the API key (401). Check UNISMS_KEY.',
            422 => 'UniSMS rejected the request (422)'.($detail ? ': '.$detail : '.')
                   .' An unregistered sender ID is the usual cause.',
            429 => 'Rate limited by UniSMS (429). Try again in a few seconds.',
            default => 'UniSMS returned HTTP '.$response->status().($detail ? ': '.$detail : '.'),
        };
    }

    // ── Credits ────────────────────────────────────────────────────────────

    /** Remaining SMS credits, or null if the balance could not be read. */
    public function credits(): ?int
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth((string) config('services.unisms.key'), '')
                ->timeout((int) config('services.unisms.timeout'))
                ->acceptJson()
                ->get($this->endpoint('account'));

            return $response->successful() ? (int) $response->json('sms_credits') : null;
        } catch (\Throwable $e) {
            Log::warning('Could not read UniSMS balance: '.$e->getMessage());

            return null;
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.unisms.base_url'), '/').'/'.$path;
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
