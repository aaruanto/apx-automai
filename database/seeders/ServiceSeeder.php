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
        $hasVtype    = Schema::hasColumn('services', 'vehicle_type');
        $hasTrans    = Schema::hasColumn('services', 'transmission');
        $now         = now();

        $services = [
            ['name' => 'Change Oil (Gasoline)',         'category' => 'Engine & Oil',       'price' => 1500, 'duration' => 45, 'description' => 'Engine oil drain and replacement with a new oil filter for gasoline engines.'],
            ['name' => 'Change Oil (Diesel)',           'category' => 'Engine & Oil',       'price' => 2200, 'duration' => 45, 'description' => 'Engine oil drain and replacement with a new oil filter for diesel engines.', 'vehicle_type' => 'car'],
            ['name' => 'Engine Tune-Up',                'category' => 'Engine & Oil',       'price' => 3500, 'duration' => 90, 'description' => 'Spark plug check, air filter service, and engine performance tuning.'],
            ['name' => 'Engine Flush',                  'category' => 'Engine & Oil',       'price' =>  800, 'duration' => 30, 'description' => 'Internal cleaning of the engine to remove sludge before an oil change.'],
            // The three services whose own names state which transmission
            // they are for. Everything else is left NULL, which means "both"
            // — a seeder should not be guessing at the shop's price list.
            ['name' => 'CVT Fluid Change',              'category' => 'CVT & Transmission', 'price' => 3800, 'duration' => 60, 'description' => 'Drain and refill of continuously variable transmission fluid.', 'transmission' => 'automatic'],
            ['name' => 'Automatic Transmission Service','category' => 'CVT & Transmission', 'price' => 3200, 'duration' => 60, 'description' => 'Automatic transmission fluid (ATF) drain and replacement.', 'vehicle_type' => 'car', 'transmission' => 'automatic'],
            ['name' => 'Manual Transmission Oil Change','category' => 'CVT & Transmission', 'price' => 1800, 'duration' => 45, 'description' => 'Gear oil drain and replacement for manual transmissions.', 'transmission' => 'manual'],
            ['name' => 'Brake Pad Replacement',         'category' => 'Brakes & Pipes',     'price' => 2500, 'duration' => 60, 'description' => 'Replacement of worn front or rear brake pads.'],
            ['name' => 'Brake Fluid Flush',             'category' => 'Brakes & Pipes',     'price' => 1200, 'duration' => 40, 'description' => 'Full brake fluid replacement and system bleeding.'],
            ['name' => 'Muffler / Exhaust Repair',      'category' => 'Brakes & Pipes',     'price' => 1500, 'duration' => 60, 'description' => 'Inspection and repair of muffler and exhaust pipe leaks.'],
            ['name' => 'General Vehicle Inspection',    'category' => 'Inspection',         'price' =>  500, 'duration' => 30, 'description' => 'Overall condition check of major vehicle systems.'],
            ['name' => 'Pre-Trip Safety Inspection',    'category' => 'Inspection',         'price' =>  700, 'duration' => 40, 'description' => 'Safety-focused check before long-distance travel.'],
            ['name' => 'Aircon Performance Check',       'category' => 'Inspection',        'price' =>  600, 'duration' => 30, 'description' => 'Air-conditioning cooling and pressure diagnostic.', 'vehicle_type' => 'car'],
            ['name' => 'Free Car Wash',                 'category' => 'Free Services',      'price' => 0, 'duration' => 30, 'description' => 'Complimentary exterior car wash with any paid service.', 'vehicle_type' => 'car'],
            ['name' => 'Free Vehicle Health Check',     'category' => 'Free Services',      'price' => 0, 'duration' => 20, 'description' => 'Complimentary quick visual health check of your vehicle.'],
            ['name' => 'Tire Pressure & Fluid Top-up',  'category' => 'Free Services',      'price' => 0, 'duration' => 15, 'description' => 'Complimentary tire pressure check and fluid top-up.'],
        ];

        foreach ($services as $s) {
            // Create what is missing, never touch what is there.
            //
            // This was updateOrInsert, which overwrote price, duration,
            // category and is_active on every run — and the Docker entrypoint
            // runs this seeder on every container start. Render's free tier
            // sleeps after fifteen minutes and restarts on the next request,
            // so a price the shop corrected in Admin was silently reset to the
            // seeded figure within the hour, and a service they switched off
            // came back on. Seed data is a starting point, not the authority.
            //
            // This stays a pure insert, including for the transmission column
            // that arrived after these rows did. Filling a NULL transmission
            // in here would have looked harmless — "only where it is still
            // NULL" — but NULL is also what the shop writes when they choose
            // "Both" in Admin, so the two are indistinguishable from this
            // side. A shop widening "Manual Transmission Oil Change" back to
            // Both would have found it narrowed again the next time Render
            // woke the container up, which is precisely the fault this
            // seeder was rewritten to stop doing.
            //
            // The one-time backfill for rows that predate the column lives in
            // 2026_11_17_090100_add_transmission_to_services_table instead,
            // where it runs once and can never second-guess a later decision.
            if (DB::table('services')->where('name', $s['name'])->exists()) {
                continue;
            }

            $row = [
                'name'        => $s['name'],
                'description' => $s['description'],
                'price'       => $s['price'],
                'duration'    => $s['duration'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
            if ($hasCategory) $row['category']  = $s['category'];
            if ($hasActive)   $row['is_active'] = true;
            // Null means every vehicle, so only the narrowed ones carry a value.
            if ($hasVtype && ! empty($s['vehicle_type'])) $row['vehicle_type'] = $s['vehicle_type'];
            // Null means both transmissions, so only the narrowed ones carry
            // a value — same convention as vehicle_type above.
            if ($hasTrans && ! empty($s['transmission'])) $row['transmission'] = $s['transmission'];

            DB::table('services')->insert($row);
        }
    }
}