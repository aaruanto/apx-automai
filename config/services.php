<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    |
    | SMS_DRIVER picks who carries a text: log, unisms or semaphore. Everything
    | else about sending is the same whichever is set — GSM-7 folding, number
    | normalisation, segment counting, the sms_logs row — so the provider is a
    | config value rather than a rewrite. See App\Services\Sms.
    |
    | It exists because a Philippine carrier has to approve an alphanumeric
    | sender name before a branded message is delivered, and that approval is
    | weeks of paperwork from a third party. Work does not stop meanwhile: log
    | sends nothing and costs nothing, and a provider with an unregistered
    | default sender can carry real messages while a branded name is in review.
    |
    | Defaults to log, and an unrecognised value falls back to it. A provider
    | that spends real credits is chosen deliberately, never by a typo.
    |
    */
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    /*
    | UniSMS. The key goes out as HTTP basic auth rather than a form field, and
    | a sender ID is required on every message.
    |
    | Dropped once, when its free sender ID stopped reaching Smart, TNT and Sun
    | — a carrier registration matter rather than anything in the API. Kept now
    | because that default sender needs no registration at all, which makes it
    | the one provider that can send while a branded name is being approved.
    | Expect Globe and DITO to arrive and Smart, TNT and Sun not to; confirm a
    | real message lands on the intended handset before relying on it.
    */
    'unisms' => [
        'key'       => env('UNISMS_KEY'),
        'sender_id' => env('UNISMS_SENDER_ID'),
        'base_url'  => env('UNISMS_BASE_URL', 'https://unismsapi.com/api'),
        'timeout'   => (int) env('UNISMS_TIMEOUT', 15),
    ],

    /*
    | Semaphore. Philippine gateway, routed domestically, reaching Globe,
    | Smart, Sun and DITO.
    |
    | It cannot send at all until a sender name is APPROVED ON THE ACCOUNT.
    | This comment used to claim the default name reached every network with no
    | registration, which is false and cost real time: with none registered
    | every call fails with HTTP 500 and "No active sender name found", which
    | reads like a key or billing problem and is neither. Credits and sender
    | names are separate things; topping up grants nothing.
    |
    | SEMAPHORE_SENDER_NAME is optional, and normally left unset: Semaphore
    | then uses the name registered to the account. Leaving SEMAPHORE_KEY unset
    | switches SMS off — the app skips the channel and says so, rather than
    | failing.
    */
    'semaphore' => [
        'key'         => env('SEMAPHORE_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME'),
        'base_url'    => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),
        'timeout'     => (int) env('SEMAPHORE_TIMEOUT', 15),
    ],

];
