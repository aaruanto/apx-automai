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
            'email_subject'   => 'Reminder: Your {service} appointment is on {date}',
            'email_preheader' => 'Hi {name}, just a reminder about your upcoming {service}.',
            'email_body'      => '<p>Hi {name}, your {service} is on {date} at {time}.</p>',
            'sms_body'        => 'Hi {name}! Reminder from APX AutoMai. Your {service} is on {date} at {time}. Ref: {reference}.',

            // ── Maintenance ────────────────────────────────────────────────
            'maintenance_subject' => 'Your {vehicle} is due for servicing',
            'maintenance_body'    => '<p>Hi {first_name},</p>'
                .'<p>Our records show your {vehicle} ({plate}) is due for servicing on <strong>{due_date}</strong> — {reason}.</p>'
                .'<p>We recommend booking <strong>{recommendation}</strong>. You can reserve a slot here: {booking_url}</p>'
                .'<p>If you have had this done elsewhere, just ignore this message.</p>'
                .'<p>— {branch}</p>',
            'maintenance_sms'     => 'Hi {first_name}! Your {plate} is due for servicing on {due_date}. We suggest {recommendation}. Book at {branch} or call {contact}.',

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
}
