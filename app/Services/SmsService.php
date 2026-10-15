<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Semaphore gateway.
 *
 * POST {base}/messages with the key as an ordinary form field — not a header,
 * not basic auth — alongside number, message and an optional sendername. The
 * reply is a JSON *array* of created messages, one per recipient.
 *
 * Replaced UniSMS, whose free sender ID stopped reaching Smart, TNT and Sun.
 * Semaphore routes domestically and reaches every network on its default
 * sender name with no registration. Everything above the HTTP call — number
 * normalisation, segment counting, logging — is unchanged by that move, and
 * the UniSMS implementation is in the git history if its coverage returns.
 *
 * Every attempt is written to sms_logs whatever the outcome, because the
 * question afterwards is always "did the customer actually get a text", and
 * the gateway's own dashboard is not where someone running this shop will
 * think to look.
 */
class SmsService
{
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

    /**
     * The key alone. Unlike UniSMS, the sender name is optional — Semaphore
     * falls back to the one registered to the account.
     */
    public function configured(): bool
    {
        return filled(config('services.semaphore.key'));
    }

    /** Why SMS is unavailable, phrased for an admin to act on. Null when fine. */
    public function unavailableReason(): ?string
    {
        return blank(config('services.semaphore.key'))
            ? 'SEMAPHORE_KEY is not set, so SMS is switched off.'
            : null;
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
                'Message is '.mb_strlen($content).' characters; this app refuses anything over '.self::MAX_LENGTH.'.',
                $recipient, $content, $context, 'too_long'
            );
        }

        // Semaphore silently discards any message beginning with "TEST": it
        // returns a normal success and nothing is delivered. Catching it here
        // turns an invisible non-delivery into a stated refusal.
        if (preg_match('/^\s*TEST/i', $content) === 1) {
            return $this->fail(
                'Semaphore silently discards messages starting with "TEST" — it reports success and delivers nothing. Reword the template so it does not begin with that word.',
                $recipient, $content, $context, 'test_prefix'
            );
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.semaphore.timeout'))
                ->acceptJson()
                ->post($this->endpoint('messages'), array_filter([
                    // The key is an ordinary form field here, not a header.
                    'apikey'     => (string) config('services.semaphore.key'),
                    // Semaphore takes 09XXXXXXXXX or 639XXXXXXXXX, without a
                    // leading plus; we keep E.164 for the log and strip it here.
                    'number'     => ltrim($recipient, '+'),
                    'message'    => $content,
                    'sendername' => (string) config('services.semaphore.sender_name'),
                ], fn ($value) => $value !== '' && $value !== null));
        } catch (\Throwable $e) {
            return $this->fail(
                'Could not reach Semaphore: '.$e->getMessage(),
                $recipient, $content, $context, 'transport_error'
            );
        }

        if (! $response->successful()) {
            return $this->fail(
                $this->describeFailure($response),
                $recipient, $content, $context, 'http_'.$response->status()
            );
        }

        // The reply is an array of created messages, one per recipient, even
        // for a single send.
        $body    = $response->json();
        $message = is_array($body) ? ($body[0] ?? $body) : [];

        $status    = strtolower((string) ($message['status'] ?? 'queued'));
        $reference = $message['message_id'] ?? null;

        // A message Semaphore itself marks Failed is not a success, however
        // healthy the HTTP status was.
        if ($status === 'failed') {
            return $this->fail(
                'Semaphore accepted the request but marked the message Failed'
                    .(isset($message['network']) ? ' on '.$message['network'] : '').'.',
                $recipient, $content, $context, 'failed'
            );
        }

        $log = $this->log($context + [
            'recipient'    => $recipient,
            'content'      => $content,
            'status'       => $status,
            'reference_id' => $reference ? (string) $reference : null,
            'segments'     => $this->segments($content)['segments'],
        ]);

        return [
            'ok'      => true,
            'message' => 'Queued with Semaphore'.($reference ? ' (id '.$reference.')' : '').'.',
            'log'     => $log,
        ];
    }

    /**
     * Turn a failed response into something an admin can act on. Where the
     * gateway explains itself, quote it; where it does not, say what the
     * status means for this app rather than restating the status line.
     */
    private function describeFailure(Response $response): string
    {
        $detail = $this->extractDetail($response);

        return match ($response->status()) {
            401 => 'Semaphore rejected the API key (401). Check SEMAPHORE_KEY.',
            // 403 is "authenticated but not permitted", which is a different
            // problem from a wrong key: an account awaiting approval, or one
            // with no credit to spend. Saying "check your key" here sends the
            // reader to the one thing that is probably fine.
            403 => 'Semaphore refused the request (403)'.($detail ? ': '.$detail : '.')
                   .' The key reached them, so this is an account-level block —'
                   .' usually an account not yet approved for sending, or one with no credits.'
                   .' Check the balance and account status in the Semaphore dashboard.',
            402 => 'Out of Semaphore credits. Top up the account to resume sending.',
            // Only guess at a cause when the gateway did not give one.
            422 => $detail
                ? 'Semaphore rejected the request (422): '.$detail
                : 'Semaphore rejected the request (422). An unregistered sender name is the usual cause.',
            429 => 'Rate limited by Semaphore (429). The limit is 120 sends a minute.',
            default => 'Semaphore returned HTTP '.$response->status().($detail ? ': '.$detail : '.'),
        };
    }

    /**
     * Whatever the gateway said about the failure.
     *
     * Checking only two known keys meant a 422 naming the offending field came
     * back as a bare "rejected the request", which is the one case where the
     * detail is the whole answer. So: try the documented keys, then the common
     * validation-bag shapes, and failing both fall back to the raw body rather
     * than discarding it.
     */
    private function extractDetail(Response $response): ?string
    {
        foreach (['message', 'error', 'detail', 'errors'] as $key) {
            $value = $response->json($key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            // A validation bag: {"errors": {"sender_id": ["is not registered"]}}
            if (is_array($value) && $value !== []) {
                $parts = [];

                foreach ($value as $field => $messages) {
                    $messages = is_array($messages) ? $messages : [$messages];
                    $parts[]  = (is_string($field) ? $field.': ' : '')
                        .implode(' ', array_filter($messages, 'is_string'));
                }

                return \Illuminate\Support\Str::limit(implode('; ', array_filter($parts)), 300);
            }
        }

        $body = trim((string) $response->body());

        return $body === '' ? null : \Illuminate\Support\Str::limit($body, 300);
    }

    // ── Credits ────────────────────────────────────────────────────────────

    /** Remaining SMS credits, or null if the balance could not be read. */
    public function credits(): ?int
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('services.semaphore.timeout'))
                ->acceptJson()
                ->get($this->endpoint('account'), [
                    'apikey' => (string) config('services.semaphore.key'),
                ]);

            if (! $response->successful()) {
                return null;
            }

            // Documented as an object, but the messages endpoint returns an
            // array for a single item, so tolerate either shape here too.
            $body    = $response->json();
            $account = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : ($body ?? []);

            return isset($account['credit_balance']) ? (int) $account['credit_balance'] : null;
        } catch (\Throwable $e) {
            Log::warning('Could not read Semaphore balance: '.$e->getMessage());

            return null;
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.semaphore.base_url'), '/').'/'.$path;
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
