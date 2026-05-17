<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('services')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('services')->insert([

            // ── Engine & Oil ───────────────────────────────────────────────
            ['name'=>'Change Oil & Filter',                  'description'=>'Complete engine oil drain and refill with high-quality oil and a fresh filter.',       'price'=>500.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Fuel Injection Cleaning',              'description'=>'Deep cleaning of fuel injectors to restore proper fuel atomization.',                   'price'=>800.00,  'duration'=>60, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Throttle Body Cleaning',               'description'=>'Remove carbon buildup from the throttle body for smoother idling.',                     'price'=>600.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Throttle Idle Adjustment',             'description'=>'Fine-tune idle speed to manufacturer specs.',                                           'price'=>400.00,  'duration'=>25, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Valve Clearance Adjustment / Tune-up', 'description'=>'Inspect and adjust valve clearances to ensure proper engine breathing.',                'price'=>1200.00, 'duration'=>75, 'created_at'=>now(),'updated_at'=>now()],

            // ── CVT & Transmission ────────────────────────────────────────
            ['name'=>'CVT Cleaning and Inspection',          'description'=>'Full CVT belt and pulley inspection with cleaning.',                                    'price'=>1500.00, 'duration'=>90, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'V-belt Inspection',                    'description'=>'Inspect V-belt for cracks, glazing, and wear.',                                        'price'=>300.00,  'duration'=>20, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'V-belt Cleaning',                      'description'=>'Clean V-belt surfaces and housing.',                                                   'price'=>350.00,  'duration'=>25, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pulley Set Inspection',                'description'=>'Full inspection of drive and driven pulley sets.',                                      'price'=>400.00,  'duration'=>30, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pulley Set Cleaning',                  'description'=>'Detailed cleaning of pulley faces and grooves.',                                        'price'=>450.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Torque Drive Assy Inspection',         'description'=>'Inspect the torque drive assembly for wear.',                                           'price'=>400.00,  'duration'=>30, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Torque Drive Assy Cleaning',           'description'=>'Clean all torque drive assembly components.',                                           'price'=>450.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Torque Drive Assy Greasing',           'description'=>'Apply fresh grease to torque drive components.',                                        'price'=>350.00,  'duration'=>25, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Clutch Lining Set / Assy Inspection',  'description'=>'Measure clutch lining thickness and check assembly.',                                   'price'=>400.00,  'duration'=>30, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Clutch Lining Set / Assy Cleaning',    'description'=>'Clean clutch lining components and housing.',                                           'price'=>450.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pulley Shaving & Re-Angle',            'description'=>'Precision machining of drive pulley face.',                                             'price'=>2000.00, 'duration'=>75, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pulley Drive Face Shaving & Re-Angle', 'description'=>'Re-angle and resurface the drive face pulley.',                                         'price'=>2200.00, 'duration'=>75, 'created_at'=>now(),'updated_at'=>now()],

            // ── Inspection ────────────────────────────────────────────────
            ['name'=>'Air Filter Inspection',                'description'=>'Visual and performance check of the air filter element.',                               'price'=>200.00,  'duration'=>15, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Air Filter Installation',              'description'=>'Supply and installation of a new OEM-spec air filter.',                                 'price'=>500.00,  'duration'=>20, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Flyball Inspection',                   'description'=>'Check flyball condition and wear for proper CVT engagement.',                           'price'=>300.00,  'duration'=>30, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Flyball Cleaning',                     'description'=>'Thorough cleaning of flyball weights.',                                                 'price'=>350.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kick Starter Inspection',              'description'=>'Check kick starter mechanism for wear.',                                                'price'=>250.00,  'duration'=>20, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Sprocket/Chain Cleaning & Regreasing', 'description'=>'Clean and relube sprocket and chain drive components.',                                 'price'=>500.00,  'duration'=>35, 'created_at'=>now(),'updated_at'=>now()],

            // ── Brakes & Pipes ────────────────────────────────────────────
            ['name'=>'Pipe Cleaning',                        'description'=>'Flush and clean fuel and coolant pipes.',                                               'price'=>600.00,  'duration'=>30, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Brake Cleaning',                       'description'=>'Degrease brake pads, discs, and drums.',                                               'price'=>500.00,  'duration'=>40, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Brake Adjustment',                     'description'=>'Adjust brake cable tension and drum/disc clearance.',                                   'price'=>400.00,  'duration'=>25, 'created_at'=>now(),'updated_at'=>now()],

            // ── Free Services ─────────────────────────────────────────────
            ['name'=>'FREE ECU Diagnose',                    'description'=>'Complimentary ECU scan using professional diagnostic tools.',                           'price'=>0.00,    'duration'=>20, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'FREE Basic Inspection',                'description'=>'Complimentary 20-point visual inspection.',                                             'price'=>0.00,    'duration'=>20, 'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}