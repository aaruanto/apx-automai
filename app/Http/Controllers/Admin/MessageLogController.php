<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\SmsLog;
use App\Services\SmsService;

/**
 * What the app actually sent, as opposed to what it is configured to send.
 *
 * Message Templates answers "what goes out"; nothing answered "did it".
 * Until this page, "the customer says they never got a reminder" could only
 * be settled by opening a database client, which on Render means an external
 * connection because the free tier has no shell.
 *
 * Two tables, deliberately not joined into one feed. They answer different
 * questions and neither is a subset of the other:
 *
 *   reminders  did this customer get told about this booking, on which
 *              channel, exactly once. Covers email and SMS.
 *   sms_logs   every text attempted, what it cost in segments, and why any
 *              of them failed. Covers test sends, which no reminder row
 *              exists for.
 *
 * Joining them would mean matching on (booking_id, trigger) or
 * (vehicle_id, trigger) and silently mis-attributing whenever that match is
 * ambiguous. Showing an SMS reminder in both is the lesser cost, and the
 * page says why.
 */
class MessageLogController extends Controller
{
    /**
     * Rows per table. Nothing else in Admin paginates, so this caps rather
     * than introducing a pattern the rest of the panel does not use. Both
     * tables are newest-first, which is the question people actually ask.
     */
    private const LIMIT = 200;

    public function index(SmsService $sms)
    {
        // A claimed-but-not-sent row is a reminder that failed and has not yet
        // been released, or one being sent by a tick happening right now.
        // Shown rather than hidden: a claim that stays put is the shape a
        // genuine problem takes here.
        $reminders = Reminder::with(['user', 'vehicle', 'booking', 'rule'])
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get();

        $texts = SmsLog::with(['booking.customer', 'vehicle'])
            ->recentFirst()
            ->limit(self::LIMIT)
            ->get();

        return view('admin.templates.log', [
            'reminders' => $reminders,
            'texts'     => $texts,
            'driver'    => $sms->driver(),
            'summary'   => $this->summary(),
        ]);
    }

    /**
     * Counts over the whole table, not over the capped lists above, so the
     * figures do not quietly change meaning once there are more than 200 rows.
     */
    private function summary(): array
    {
        return [
            'reminders_sent'  => Reminder::where('status', 'sent')->count(),
            'reminders_stuck' => Reminder::where('status', 'claimed')->count(),

            'texts_total'     => SmsLog::count(),
            'texts_failed'    => SmsLog::failures()->count(),
            'texts_simulated' => SmsLog::simulations()->count(),

            // The cost proxy. Segments, not messages: one non-GSM-7 character
            // cuts a segment from 160 characters to 70, so a template that
            // quietly grew is visible here and nowhere else in the panel.
            'segments_billed' => (int) SmsLog::whereIn('status', SmsLog::ACCEPTED)->sum('segments'),
        ];
    }
}
