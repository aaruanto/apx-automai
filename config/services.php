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
    | Semaphore
    |--------------------------------------------------------------------------
    |
    | Philippine SMS gateway, routed domestically, reaching Globe, Smart, Sun
    | and DITO on the default sender name without any registration.
    |
    | This replaced UniSMS, whose free sender ID stopped reaching Smart, TNT
    | and Sun — a carrier registration matter rather than anything in the API.
    | The UniSMS implementation is in the git history if that coverage returns.
    |
    | SEMAPHORE_SENDER_NAME is optional: left unset, Semaphore uses the sender
    | name registered to the account. Leaving SEMAPHORE_KEY unset switches SMS
    | off entirely — the app skips the channel and says so, rather than failing.
    |
    */
    'semaphore' => [
        'key'         => env('SEMAPHORE_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME'),
        'base_url'    => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),
        'timeout'     => (int) env('SEMAPHORE_TIMEOUT', 15),
    ],

];
