<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin provisioning
    |--------------------------------------------------------------------------
    |
    | Read by the apx:ensure-admin command, which the Docker entrypoint runs on
    | every boot so a fresh deploy has an account to sign in with. These live in
    | config rather than being read with env() directly, because env() returns
    | null once config:cache has run — which is exactly what happens in
    | production, and exactly when admin provisioning matters.
    |
    | ADMIN_PASSWORD should be removed from the environment once a real admin
    | account exists; the command treats absent values as a normal state and
    | skips provisioning rather than failing the boot.
    |
    */

    'admin' => [
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'name'     => env('ADMIN_NAME', 'Administrator'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shop details
    |--------------------------------------------------------------------------
    |
    | Substituted into the {branch} and {contact} message placeholders. The
    | reminders command already read these two keys before this file existed,
    | so both resolved to null and every reminder carried an empty branch name.
    |
    */
    'branch_name'    => env('APX_BRANCH_NAME', 'APX Motors — Tandang Sora'),
    'contact_number' => env('APX_CONTACT_NUMBER', ''),

];
