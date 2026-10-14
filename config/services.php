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
    | UniSMS
    |--------------------------------------------------------------------------
    |
    | Philippine SMS gateway (Globe, Smart, DITO, Sun, TNT). Authentication is
    | HTTP Basic with the secret key as the username and an empty password.
    |
    | The sender ID is required on every message. New accounts are issued a
    | random one for testing; a registered sender ID has to be applied for
    | before production traffic, which is a telco requirement here rather than
    | a UniSMS one.
    |
    | Leaving UNISMS_KEY unset disables SMS entirely — the app skips the
    | channel and says so, rather than failing.
    |
    */
    'unisms' => [
        'key'       => env('UNISMS_KEY'),
        'sender_id' => env('UNISMS_SENDER_ID'),
        'base_url'  => env('UNISMS_BASE_URL', 'https://unismsapi.com/api'),
        'timeout'   => (int) env('UNISMS_TIMEOUT', 15),
    ],

];
