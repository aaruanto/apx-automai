<?php

// Run this from your Laravel root:
// php seed_services.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$services = [
    'Change Oil & Filter',
    'Fuel Injection Cleaning',
    'Throttle Body Cleaning',
    'Throttle Idle Adjustment',
    'Valve Clearance Adjustment / Tune-up',
    'CVT Cleaning and Inspection',
    'Air Filter Inspection',
    'Air Filter Installation',
    'Flyball Inspection',
    'Flyball Cleaning',
    'V-belt Inspection',
    'V-belt Cleaning',
    'Pulley Set Inspection',
    'Pulley Set Cleaning',
    'Torque Drive Assy Inspection',
    'Torque Drive Assy Cleaning',
    'Torque Drive Assy Greasing',
    'Clutch Lining Set / Assy Inspection',
    'Clutch Lining Set / Assy Cleaning',
    'Kick Starter Inspection',
    'Pulley Shaving & Re-Angle',
    'Pulley Drive Face Shaving & Re-Angle',
    'Sprocket/Chain Cleaning & Regreasing',
    'Pipe Cleaning',
    'Brake Cleaning',
    'Brake Adjustment',
    'FREE ECU Diagnose',
    'FREE Basic Inspection',
];

foreach ($services as $name) {
    \App\Models\Service::firstOrCreate(
        ['name' => $name],
        ['price' => 0.00, 'description' => '', 'duration' => 30, 'is_active' => 1]
    );
}

$count = \App\Models\Service::count();
$firstId = \App\Models\Service::first()->id;

echo "Done! {$count} services in DB. First ID: {$firstId}\n";