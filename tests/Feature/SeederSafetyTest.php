<?php

use App\Models\Bundle;
use App\Models\MaintenanceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Seed data creates what is missing and never overwrites what the shop changed.
 *
 * The Docker entrypoint runs these seeders on every container start, and
 * Render's free tier sleeps after fifteen minutes and restarts on the next
 * request. They used to updateOrInsert, so a price the shop corrected in Admin
 * was silently reset to the seeded figure within the hour, and a service they
 * switched off came back on.
 */

it('creates the service catalogue on an empty database', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    expect(DB::table('services')->count())->toBeGreaterThan(10);
});

it('never overwrites a price the shop corrected', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    DB::table('services')->where('name', 'Change Oil (Gasoline)')
        ->update(['price' => 1750, 'is_active' => false]);

    // The next container restart.
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    $service = DB::table('services')->where('name', 'Change Oil (Gasoline)')->first();

    expect((float) $service->price)->toBe(1750.0);
    expect((bool) $service->is_active)->toBeFalse();
});

it('restores a service the shop deleted, without touching the rest', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    DB::table('services')->where('name', 'Engine Flush')->update(['price' => 950]);
    DB::table('services')->where('name', 'Pre-Trip Safety Inspection')->delete();

    $this->seed(\Database\Seeders\ServiceSeeder::class);

    expect(DB::table('services')->where('name', 'Pre-Trip Safety Inspection')->exists())->toBeTrue();
    expect((float) DB::table('services')->where('name', 'Engine Flush')->first()->price)->toBe(950.0);
});

it('leaves a package the shop retuned alone', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);
    $this->seed(\Database\Seeders\MaintenancePackageSeeder::class);

    $bundle = Bundle::where('name', 'Basic Service')->sole();
    $bundle->update(['price' => 1500, 'is_active' => false]);

    $this->seed(\Database\Seeders\MaintenancePackageSeeder::class);

    expect((float) $bundle->fresh()->price)->toBe(1500.0);
    expect($bundle->fresh()->is_active)->toBeFalse();
    expect(Bundle::where('name', 'Basic Service')->count())->toBe(1);
});

it('leaves a service interval the shop switched off switched off', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);
    $this->seed(\Database\Seeders\MaintenancePackageSeeder::class);
    $this->seed(\Database\Seeders\MaintenanceRuleSeeder::class);

    $rule = MaintenanceRule::where('name', 'Basic PMS due')->sole();
    $rule->update(['interval_months' => 9, 'is_active' => false]);

    // Switching an interval off must survive a restart, or the shop cannot
    // actually turn a reminder off at all.
    $this->seed(\Database\Seeders\MaintenanceRuleSeeder::class);

    expect($rule->fresh()->interval_months)->toBe(9);
    expect($rule->fresh()->is_active)->toBeFalse();
});

it('is safe to run every seeder repeatedly', function () {
    foreach (range(1, 3) as $ignored) {
        $this->seed(\Database\Seeders\ServiceSeeder::class);
        $this->seed(\Database\Seeders\MaintenancePackageSeeder::class);
        $this->seed(\Database\Seeders\MaintenanceRuleSeeder::class);
    }

    expect(Bundle::where('name', 'Basic Service')->count())->toBe(1);
    expect(MaintenanceRule::where('name', 'Basic PMS due')->count())->toBe(1);
    expect(DB::table('services')->where('name', 'Engine Flush')->count())->toBe(1);
});
