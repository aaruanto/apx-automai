<?php

namespace App\Services\Sms;

/**
 * One provider's half of sending a text.
 *
 * SmsService owns everything that is true whoever carries the message:
 * GSM-7 folding, number normalisation, segment counting, the length limit,
 * the sms_logs row, and the ok/failed shape the reminder commands read. A
 * gateway owns only the part that differs — which credentials it needs, the
 * HTTP call, and how to read the answer.
 *
 * That split is not new. The commit that moved this app from UniSMS to
 * Semaphore noted "the swap touched only the HTTP call", which is exactly the
 * seam this interface draws. Making it explicit means the provider becomes a
 * config value instead of a rewrite — which matters when one of them is
 * waiting on a carrier approval nobody here controls.
 */
interface SmsGateway
{
    /** Short name for messages and logs: 'log', 'unisms', 'semaphore'. */
    public function name(): string;

    /** Whether this gateway has what it needs to send. */
    public function configured(): bool;

    /** Why it cannot send, phrased for an admin to act on. Null when fine. */
    public function unavailableReason(): ?string;

    /**
     * Content this provider will not carry, phrased for whoever wrote it.
     *
     * Semaphore silently discards anything beginning with "TEST" — it reports
     * success and delivers nothing — so that has to be refused before it is
     * logged as sent. Providers without such a quirk return null.
     *
     * The status travels with the reason because it is what lands in
     * sms_logs, and "refused_by_gateway" on every such row would throw away
     * the one thing the reader needs: which rule was broken.
     *
     * @return array{status: string, reason: string}|null
     */
    public function refuses(string $content): ?array;

    /**
     * Hand the message over. The recipient is already E.164 and the content
     * is already GSM-7 folded and length-checked.
     *
     * Must not throw: a provider being unreachable is an outcome, not an
     * exception, and the caller writes an sms_logs row either way.
     *
     * @return array{ok: bool, status: string, reference: ?string, message: string}
     *     status is stored in sms_logs — 'queued'/'sent' on success, or a
     *     short machine-ish reason such as 'transport_error' or 'http_401'.
     */
    public function dispatch(string $recipient, string $content): array;

    /** Remaining credits, or null when the provider cannot say. */
    public function credits(): ?int;
}
