<?php

namespace App\Support;

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

    public static function defaults(): array
    {
        return [
            'email_subject'   => 'Reminder: your {service} is on {date}',
            'email_preheader' => 'Hi {first_name}, just a reminder about your upcoming {service}.',
            'email_body'      => self::appointmentBody(),

            // Kept to one segment deliberately. {branch} is not used here: the
            // configured name carries an em-dash, which is outside GSM-7 and
            // would push the whole message to 70 characters a segment.
            'sms_body'        => 'Hi {first_name}! APX AutoMai: your {service} is on {date}, {time}. Ref {reference}. Please arrive 10 mins early.',

            // ── Maintenance ────────────────────────────────────────────────
            'maintenance_subject' => 'Your {vehicle} is due for servicing',
            'maintenance_body'    => self::maintenanceBody(),
            // Also avoids {contact}, for the same reason as the email footer:
            // unset, it left "Call APX AutoMai on  to book." going out by text.
            'maintenance_sms'     => 'Hi {first_name}! Your {plate} is due for servicing on {due_date}. We suggest {recommendation}. Reply or drop by APX AutoMai to book.',

            'triggers' => [
                'remind_7d'        => true,
                'remind_3d'        => true,
                'remind_1d'        => true,
                'remind_2h'        => false,
                'maintenance_due'  => true,
            ],
            'channels' => [
                'send_email' => true,
                'send_sms'   => true,
            ],
            'sender_name' => 'APX AutoMai',
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
            .'<div style="color:#ffffff;font-size:18px;font-weight:bold;letter-spacing:.5px;">APX AutoMai</div>'
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
