<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Semaphore. Philippine gateway, routed domestically.
 *
 * Moved here unchanged from SmsService when the provider became a config
 * value. Every quirk below was learned the expensive way; see CLAUDE.md.
 *
 * It cannot send at all until a sender name is approved on the account.
 * Credits and sender names are separate things and topping up grants
 * nothing — with none registered every call fails with HTTP 500 and
 * "No active sender name found", which reads like a key or billing problem
 * and is neither.
 */
class SemaphoreGateway implements SmsGateway
{
    public function name(): string
    {
        return 'semaphore';
    }

    public function configured(): bool
    {
        return filled(config('services.semaphore.key'));
    }

    public function unavailableReason(): ?string
    {
        return blank(config('services.semaphore.key'))
            ? 'SEMAPHORE_KEY is not set, so SMS is switched off.'
            : null;
    }

    public function refuses(string $content): ?array
    {
        // Semaphore silently discards any message beginning with "TEST": it
        // returns a normal success and nothing is delivered. Catching it here
        // turns an invisible non-delivery into a stated refusal.
        return preg_match('/^\s*TEST/i', $content) === 1
            ? [
                'status' => 'test_prefix',
                'reason' => 'Semaphore silently discards messages starting with "TEST" — it reports success and delivers nothing. Reword the template so it does not begin with that word.',
            ]
            : null;
    }

    public function dispatch(string $recipient, string $content): array
    {
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
                    // Trimmed, because array_filter below rejects '' and null
                    // but not ' '. A stray space typed into the host's env UI
                    // would send a blank sender name and come back as "No active
                    // sender name found" — while the variable still looks empty.
                    'sendername' => trim((string) config('services.semaphore.sender_name')),
                ], fn ($value) => $value !== '' && $value !== null));
        } catch (\Throwable $e) {
            return $this->failure('Could not reach Semaphore: '.$e->getMessage(), 'transport_error');
        }

        if (! $response->successful()) {
            return $this->failure($this->describeFailure($response), 'http_'.$response->status());
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
            return $this->failure(
                'Semaphore accepted the request but marked the message Failed'
                    .(isset($message['network']) ? ' on '.$message['network'] : '').'.',
                'failed'
            );
        }

        return [
            'ok'        => true,
            'status'    => $status,
            'reference' => $reference ? (string) $reference : null,
            'message'   => 'Queued with Semaphore'.($reference ? ' (id '.$reference.')' : '').'.',
        ];
    }

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

    private function failure(string $message, string $status): array
    {
        return ['ok' => false, 'status' => $status, 'reference' => null, 'message' => $message];
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.semaphore.base_url'), '/').'/'.ltrim($path, '/');
    }
}
