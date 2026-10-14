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

];
