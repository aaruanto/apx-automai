<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The reminder templates, and the single place that knows where they live.
 *
 * They used to be loaded in two places with two different paths — the admin
 * panel saved to "private/templates/reminder.json" while the reminders command
 * read "templates/reminder.json" — on a disk already rooted at
 * storage/app/private. So the admin panel wrote one file, the scheduler read
 * another, and editing a template changed nothing about the messages customers
 * actually received. Both now come through here.
 *
 * Two kinds of message share this file. An APPOINTMENT reminder is about a
 * booking the customer already made. A MAINTENANCE reminder is about a vehicle
 * that is due for servicing and has nothing booked — the system starting the
 * conversation rather than confirming it.
 */
class MessageTemplate
{
    /** Relative to the local disk, whose root is already storage/app/private. */
    public const PATH = 'templates/reminder.json';

    /** Placeholders both kinds of message share. */
    public const PLACEHOLDERS = [
        '{name}'       => "The customer's full name",
        '{first_name}' => 'First name only',
        '{service}'    => 'Service booked',
        '{date}'       => 'Booking date, e.g. March 16, 2026',
        '{time}'       => 'Booking time, e.g. 9:00 AM',
        '{plate}'      => 'Vehicle plate number',
        '{vehicle}'    => 'Make, model and year',
        '{reference}'  => 'Booking reference',
        '{branch}'     => 'Branch name',
        '{contact}'    => 'Shop contact number',
        '{days_until}' => 'Whole days until the booking',
    ];

    /** Placeholders only a maintenance reminder can fill. */
    public const MAINTENANCE_PLACEHOLDERS = [
        '{plate}'          => 'Vehicle plate number',
        '{vehicle}'        => 'Make, model and year',
        '{name}'           => "The customer's full name",
        '{first_name}'     => 'First name only',
        '{due_date}'       => 'When the service is due, e.g. March 16, 2026',
        '{reason}'         => 'Why it is due, e.g. "6 months since the last service"',
        '{recommendation}' => 'The package or service we suggest booking',
        '{branch}'         => 'Branch name',
        '{contact}'        => 'Shop contact number',
        '{booking_url}'    => 'Link to the booking page',
    ];

    public static function load(): array
    {
        if (Storage::exists(self::PATH)) {
            $saved = json_decode((string) Storage::get(self::PATH), true);

            // A truncated or hand-edited file must not take the scheduler down;
            // fall back to defaults and fill any missing keys.
            if (is_array($saved)) {
                return array_replace_recursive(self::defaults(), $saved);
            }
        }

        return self::defaults();
    }

    public static function save(array $template): void
    {
        Storage::put(self::PATH, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Fill a template body's placeholders for one booking.
     *
     * This was a private helper on SendBookingReminders until booking
     * confirmation needed the same substitutions. It moved here rather than
     * being copied: two maps drift, and the one that drifts sends a customer
     * a message with a literal {reference} in it. It sits beside
     * PLACEHOLDERS, which is the list it has to honour.
     */
    public static function fill(string $text, Booking $booking): string
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

    public static function defaults(): array
    {
        return [
            'email_subject'   => 'Reminder: your {service} is on {date}',
            'email_preheader' => 'Hi {first_name}, just a reminder about your upcoming {service}.',
            'email_body'      => self::appointmentBody(),

            // Kept to one segment deliberately. {branch} is not used here: the
            // configured name carries an em-dash, which is outside GSM-7 and
            // would push the whole message to 70 characters a segment.
            //
            // "APX Motors", not "APX AUTOMAI": the Semaphore sender name is
            // APXMOTORS, and a text whose sender and body disagree about who
            // sent it reads like a scam. Email keeps the portal name — it has
            // no sender-ID approval to satisfy and shows a full from-address.
            'sms_body'        => 'Hi {first_name}! APX Motors: your {service} is on {date}, {time}. Ref {reference}. Please arrive 10 mins early.',
            // ── Booking confirmed ──────────────────────────────────────────
            // Sent once, when an admin moves a booking to "confirmed". Email
            // already does this; the text exists because a confirmation is the
            // message a customer is actively waiting for, and the one most
            // worth the credit.
            //
            // Same constraints as the reminder above: one segment, "APX
            // Motors" to match the sender ID, and neither {branch} nor
            // {contact} — the branch name carries an em-dash that would force
            // UCS-2, and an unset contact number leaves a dangling "on .".
            'confirmation_sms' => 'Hi {first_name}! APX Motors: your {service} on {date}, {time} is CONFIRMED. Ref {reference}. See you then!',
            // ── Booking cancelled ──────────────────────────────────────────
            // Goes out from BookingCancellation, so it covers the shop
            // cancelling, the customer cancelling and the no-show sweep alike.
            //
            // {reason} is deliberately absent. A cancellation reason is free
            // text an admin types, so putting it in a 160-character budget
            // means one long reason silently costs three segments — and a
            // reason written for the audit trail is not always one you would
            // text the customer. They are told to call instead.
            //
            // {time} is dropped, unlike the confirmation above. At 163
            // characters the first draft was two segments against the
            // longest service name on file, and a cancellation has no
            // appointment to turn up for — the date and reference are
            // enough to say which booking. 140 worst case, 20 to spare.
            'cancellation_sms' => 'Hi {first_name}! APX Motors: your {service} on {date} is CANCELLED. Ref {reference}. Call us to rebook.',

            // ── Work finished ──────────────────────────────────────────────
            // The one message a customer who dropped their vehicle off cannot
            // get any other way. Someone who waited at the counter was told
            // in person; someone who left the car has no way to know it is
            // ready until this arrives.
            //
            // {date} and {time} are dropped. They describe when the
            // appointment was, which is behind the customer now and costs
            // characters that "ready for pickup" needs. Same constraints as
            // the two above: one segment, "APX Motors" for the sender ID, no
            // {branch} or {contact}.
            //
            // 123 characters against a typical service name, 150 against the
            // longest pair on file. ServiceCompletionSmsTest holds that line.
            'completion_sms' => 'Hi {first_name}! APX Motors: your {service} is COMPLETE and your vehicle is ready for pickup. Ref {reference}. Thank you!',

            // ── Maintenance ────────────────────────────────────────────────
            'maintenance_subject' => 'Your {vehicle} is due for servicing',
            'maintenance_body'    => self::maintenanceBody(),
            // Also avoids {contact}, for the same reason as the email footer:
            // unset, it left "Call APX AUTOMAI on  to book." going out by text.
            'maintenance_sms'     => 'Hi {first_name}! Your {plate} is due for servicing on {due_date}. We suggest {recommendation}. Reply or drop by APX Motors to book.',

            'triggers' => [
                'confirm_booking'  => true,
                'cancel_booking'   => true,
                'remind_7d'        => true,
                'remind_3d'        => true,
                'remind_1d'        => true,
                // On since the schedule ticks every five minutes. It was
                // off because it could not work: the 2h window is a
                // 15-minute band at now()+2h, and against an hourly tick the
                // only bands ever examined were [H+2:00, H+2:15] — so with a
                // 30-minute slot grid, every booking on a :30 start was never
                // reminded at all. See routes/console.php and
                // tests/Feature/TwoHourReminderTest.php.
                //
                // The default matters more here than for the other windows.
                // Render's filesystem is ephemeral and this template is saved
                // to storage/app/private, which is not in the image — so a
                // deploy or a restart discards whatever an admin toggled and
                // leaves exactly this array deciding what goes out. A saved
                // value still wins while the file survives: load() merges the
                // file over these defaults.
                'remind_2h'        => true,
                'maintenance_due'  => true,
            ],
            'channels' => [
                'send_email' => true,
                'send_sms'   => true,
            ],
            'sender_name' => 'APX AUTOMAI',
            'reply_to'    => '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The default email bodies
    |--------------------------------------------------------------------------
    |
    | Sent with Mail::html(), which wraps the body in nothing at all, so each
    | one has to stand on its own. Written as tables with inline styles because
    | that is what Outlook and Gmail actually render: a flex or grid layout, an
    | external stylesheet or a <style> block is stripped or ignored by at least
    | one mail client that customers here really use.
    |
    | The appointment reminder used to be a single 56-character line with no
    | branch, no plate and no reference — so a customer with two cars could not
    | tell which one was booked, and had nothing to quote at the counter.
    |
    */

    private static function shell(string $heading, string $inner): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            .'style="background:#f4f4f5;margin:0;padding:24px 0;font-family:Arial,Helvetica,sans-serif;">'
            .'<tr><td align="center">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            .'style="max-width:560px;background:#ffffff;border-radius:10px;overflow:hidden;">'

            // Header band. A background colour with light text on it, so the
            // shop is recognisable before a word is read.
            .'<tr><td style="background:#1a1a1a;padding:20px 28px;">'
            .'<div style="color:#ffffff;font-size:18px;font-weight:bold;letter-spacing:.5px;">APX AUTOMAI</div>'
            .'<div style="color:#c8c8c8;font-size:12px;margin-top:3px;">{branch}</div>'
            .'</td></tr>'

            .'<tr><td style="padding:28px;">'
            .'<div style="font-size:17px;font-weight:bold;color:#1a1a1a;margin-bottom:14px;">'.$heading.'</div>'
            .$inner
            .'</td></tr>'

            // No {contact} here. APX_CONTACT_NUMBER is optional config, and
            // when it is unset this read "Call us on  or just reply to this
            // email." — a broken sentence in every message, caused by a
            // variable nobody had been told to set. Replying always works, so
            // the footer leans on that; the shop can add a phone number
            // through the template editor once the variable is configured.
            .'<tr><td style="background:#fafafa;border-top:1px solid #ececec;padding:18px 28px;'
            .'font-size:12px;color:#777777;line-height:1.6;">'
            .'Questions, or need to change this? Just reply to this email and we will sort it out.<br>'
            .'{branch}'
            .'</td></tr>'

            .'</table></td></tr></table>';
    }

    /** One label/value row of the details block. */
    private static function row(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:7px 0;font-size:13px;color:#777777;width:38%;vertical-align:top;">'.$label.'</td>'
            .'<td style="padding:7px 0;font-size:13px;color:#1a1a1a;font-weight:bold;">'.$value.'</td>'
            .'</tr>';
    }

    private static function appointmentBody(): string
    {
        return self::shell(
            'Your appointment is coming up',
            '<p style="font-size:14px;color:#444444;line-height:1.6;margin:0 0 18px;">'
            // {days_until} is deliberately not used. A flat template cannot
            // pluralise, so it read "in 1 day(s)" — and the date and time are
            // in the table immediately below anyway.
            .'Hi {first_name}, this is a reminder about your upcoming booking with us.'
            .'</p>'

            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            .'style="border-top:1px solid #ececec;border-bottom:1px solid #ececec;margin:0 0 18px;">'
            .self::row('Service', '{service}')
            .self::row('Date', '{date}')
            .self::row('Time', '{time}')
            // The plate matters: a customer with two cars cannot otherwise
            // tell which one this is about.
            .self::row('Vehicle', '{vehicle} &middot; {plate}')
            .self::row('Reference', '{reference}')
            .'</table>'

            .'<p style="font-size:14px;color:#444444;line-height:1.6;margin:0 0 10px;">'
            .'Please arrive about 10 minutes early and bring your reference number.'
            .'</p>'
            .'<p style="font-size:13px;color:#777777;line-height:1.6;margin:0;">'
            .'If you can no longer make it, let us know as early as you can so we can offer the slot to someone else.'
            .'</p>'
        );
    }

    private static function maintenanceBody(): string
    {
        return self::shell(
            'Your {vehicle} is due for servicing',
            '<p style="font-size:14px;color:#444444;line-height:1.6;margin:0 0 18px;">'
            .'Hi {first_name}, our records show your vehicle is due for its next service. '
            .'Nothing is booked yet.'
            .'</p>'

            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            .'style="border-top:1px solid #ececec;border-bottom:1px solid #ececec;margin:0 0 18px;">'
            .self::row('Vehicle', '{vehicle} &middot; {plate}')
            .self::row('Due', '{due_date}')
            .self::row('Why', '{reason}')
            .self::row('We recommend', '{recommendation}')
            .'</table>'

            // A plain anchor, styled to look like a button. A real button
            // element or a background-image would not survive Outlook.
            .'<p style="margin:0 0 20px;">'
            .'<a href="{booking_url}" style="display:inline-block;background:#E8192C;color:#ffffff;'
            .'text-decoration:none;font-size:14px;font-weight:bold;padding:11px 22px;border-radius:6px;">'
            .'Book this service</a>'
            .'</p>'

            .'<p style="font-size:13px;color:#777777;line-height:1.6;margin:0;">'
            .'Already had this done elsewhere? No action needed, just ignore this message.'
            .'</p>'
        );
    }
}
