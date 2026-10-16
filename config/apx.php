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

    /*
    |--------------------------------------------------------------------------
    | Guided booking
    |--------------------------------------------------------------------------
    |
    | The first step of the booking modal asks what the customer needs rather
    | than showing them a list of service names, because most people booking a
    | car in do not know what the names mean.
    |
    | Defined here rather than in the services table: re-filing services into a
    | "Periodic Maintenance" category would have emptied the Engine & Oil and
    | CVT categories the browse filters rely on, and a service can reasonably
    | be both routine maintenance and something you browse to directly. This
    | is a view of existing data, not a change to it.
    |
    */
    'booking_paths' => [

        // Tapping "General Check-up" preselects this service by name, so the
        // customer lands on the details step with something already chosen.
        // Falls back to the first service in the inspection category.
        'checkup_service'    => 'General Vehicle Inspection',
        'checkup_category'   => 'Inspection',

        // Periodic maintenance offers the bundles, not a filtered list of
        // services: most customers cannot name what their vehicle is due
        // for, which is the reason packages exist. Nothing to configure —
        // the packages are whatever is live in the bundles table.

        // Offered as symptom chips on the check-up path. Tapping one writes
        // into the notes field the details step already has, so nothing about
        // that step changes.
        'checkup_symptoms' => [
            'Strange noise',
            'Warning light on',
            'Brakes feel off',
            'Aircon not cold',
            'Hard to start',
            'Pulling to one side',
            'Overheating',
            'Something else',
        ],
    ],

];
