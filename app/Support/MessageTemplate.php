<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * The reminder template, and the single place that knows where it lives.
 *
 * It used to be loaded in two places with two different paths — the admin
 * panel saved to "private/templates/reminder.json" while the reminders command
 * read "templates/reminder.json" — on a disk already rooted at
 * storage/app/private. So the admin panel wrote one file, the scheduler read
 * another, and editing a template changed nothing about the messages customers
 * actually received. Both now come through here.
 */
class MessageTemplate
{
    /** Relative to the local disk, whose root is already storage/app/private. */
    public const PATH = 'templates/reminder.json';

    /** Placeholders the editor offers, and what each one means. */
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
            'triggers' => [
                'remind_7d' => true,
                'remind_3d' => true,
                'remind_1d' => true,
                'remind_2h' => false,
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
