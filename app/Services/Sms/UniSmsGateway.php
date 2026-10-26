<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UniSMS. Ported from the implementation this app used before Semaphore,
 * rather than rewritten — see commit 263e85d3 and the revision before it.
 *
 * It was dropped because its free sender ID stopped reaching Smart, TNT and
 * Sun. That is a carrier registration matter, not an API one, and it is the
 * same wall Semaphore is behind now. What it still has is a default sender ID
 * that works without any registration at all, which makes it the provider
 * that can send today while an application for a branded name is in review.
 *
 * Expect Globe and DITO to arrive and Smart, TNT and Sun not to. That is the
 * trade: no paperwork, narrower reach. Coverage on a shared free sender ID is
 * nobody's promise and has changed before without notice, so confirm a real
 * message arrives on the handset you intend to use before relying on it.
 *
 * Differences from Semaphore worth keeping in mind: the key is HTTP basic
 * auth rather than a form field, and the reply is a single object under a
 * "message" key rather than an array of created messages.
 */
class UniSmsGateway implements SmsGateway
{
    public function name(): string
    {
        return 'unisms';
    }

    public function configured(): bool
    {
        return filled(config('services.unisms.key'))
            && filled(config('services.unisms.sender_id'));
    }

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

    /** No known content restriction, unlike Semaphore's "TEST" prefix rule. */
    public function refuses(string $content): ?array
    {
        return null;
    }

    public function dispatch(string $recipient, string $content): array
    {
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
            return $this->failure('Could not reach UniSMS: '.$e->getMessage(), 'transport_error');
        }

        if (! $response->successful()) {
            return $this->failure($this->describeFailure($response), 'http_'.$response->status());
        }

        $message   = $response->json('message') ?: [];
        $reference = $message['reference_id'] ?? null;

        return [
            'ok'        => true,
            'status'    => (string) ($message['status'] ?? 'pending'),
            'reference' => $reference ? (string) $reference : null,
            'message'   => 'Accepted by UniSMS'.($reference ? ' (ref '.$reference.')' : '').'.',
        ];
    }

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

    private function describeFailure(Response $response): string
    {
        $detail = $this->extractDetail($response);

        return match ($response->status()) {
            401 => 'UniSMS rejected the API key (401). Check UNISMS_KEY.',
            // Only guess at the cause when the gateway did not say. Appending
            // the hint to a message that already explains itself reads as a
            // contradiction and sends the reader after the wrong thing.
            422 => $detail
                ? 'UniSMS rejected the request (422): '.$detail
                : 'UniSMS rejected the request (422). An unregistered sender ID is the usual cause.',
            429 => 'Rate limited by UniSMS (429). Try again in a few seconds.',
            default => 'UniSMS returned HTTP '.$response->status().($detail ? ': '.$detail : '.'),
        };
    }

    private function extractDetail(Response $response): ?string
    {
        $body = $response->json();

        if (! is_array($body)) {
            $raw = trim((string) $response->body());

            return $raw === '' ? null : mb_substr($raw, 0, 200);
        }

        foreach (['message', 'error', 'detail'] as $key) {
            if (isset($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }

        return null;
    }

    private function failure(string $message, string $status): array
    {
        return ['ok' => false, 'status' => $status, 'reference' => null, 'message' => $message];
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.unisms.base_url'), '/').'/'.ltrim($path, '/');
    }
}
