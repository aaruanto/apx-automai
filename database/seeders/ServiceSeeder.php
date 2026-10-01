<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $hasCategory = Schema::hasColumn('services', 'category');
        $hasActive   = Schema::hasColumn('services', 'is_active');
        $now         = now();

        $services = [
            ['name' => 'Change Oil (Gasoline)',         'category' => 'Engine & Oil',       'price' => 1500, 'duration' => 45, 'description' => 'Engine oil drain and replacement with a new oil filter for gasoline engines.'],
            ['name' => 'Change Oil (Diesel)',           'category' => 'Engine & Oil',       'price' => 2200, 'duration' => 45, 'description' => 'Engine oil drain and replacement with a new oil filter for diesel engines.'],
            ['name' => 'Engine Tune-Up',                'category' => 'Engine & Oil',       'price' => 3500, 'duration' => 90, 'description' => 'Spark plug check, air filter service, and engine performance tuning.'],
            ['name' => 'Engine Flush',                  'category' => 'Engine & Oil',       'price' =>  800, 'duration' => 30, 'description' => 'Internal cleaning of the engine to remove sludge before an oil change.'],
            ['name' => 'CVT Fluid Change',              'category' => 'CVT & Transmission', 'price' => 3800, 'duration' => 60, 'description' => 'Drain and refill of continuously variable transmission fluid.'],
            ['name' => 'Automatic Transmission Service','category' => 'CVT & Transmission', 'price' => 3200, 'duration' => 60, 'description' => 'Automatic transmission fluid (ATF) drain and replacement.'],
            ['name' => 'Manual Transmission Oil Change','category' => 'CVT & Transmission', 'price' => 1800, 'duration' => 45, 'description' => 'Gear oil drain and replacement for manual transmissions.'],
            ['name' => 'Brake Pad Replacement',         'category' => 'Brakes & Pipes',     'price' => 2500, 'duration' => 60, 'description' => 'Replacement of worn front or rear brake pads.'],
            ['name' => 'Brake Fluid Flush',             'category' => 'Brakes & Pipes',     'price' => 1200, 'duration' => 40, 'description' => 'Full brake fluid replacement and system bleeding.'],
            ['name' => 'Muffler / Exhaust Repair',      'category' => 'Brakes & Pipes',     'price' => 1500, 'duration' => 60, 'description' => 'Inspection and repair of muffler and exhaust pipe leaks.'],
            ['name' => 'General Vehicle Inspection',    'category' => 'Inspection',         'price' =>  500, 'duration' => 30, 'description' => 'Overall condition check of major vehicle systems.'],
            ['name' => 'Pre-Trip Safety Inspection',    'category' => 'Inspection',         'price' =>  700, 'duration' => 40, 'description' => 'Safety-focused check before long-distance travel.'],
            ['name' => 'Aircon Performance Check',       'category' => 'Inspection',        'price' =>  600, 'duration' => 30, 'description' => 'Air-conditioning cooling and pressure diagnostic.'],
            ['name' => 'Free Car Wash',                 'category' => 'Free Services',      'price' => 0, 'duration' => 30, 'description' => 'Complimentary exterior car wash with any paid service.'],
            ['name' => 'Free Vehicle Health Check',     'category' => 'Free Services',      'price' => 0, 'duration' => 20, 'description' => 'Complimentary quick visual health check of your vehicle.'],
            ['name' => 'Tire Pressure & Fluid Top-up',  'category' => 'Free Services',      'price' => 0, 'duration' => 15, 'description' => 'Complimentary tire pressure check and fluid top-up.'],
        ];

        foreach ($services as $s) {
            $row = [
                'description' => $s['description'],
                'price'       => $s['price'],
                'duration'    => $s['duration'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
            if ($hasCategory) $row['category']  = $s['category'];
            if ($hasActive)   $row['is_active'] = 1;

            DB::table('services')->updateOrInsert(['name' => $s['name']], $row);
        }
    }
}