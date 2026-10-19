<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\MaintenanceRule;
use Illuminate\Database\Seeder;

/**
 * The default service intervals.
 *
 * Deliberately conservative and few. Three rules the shop can defend to a
 * customer beat a dozen that generate mail nobody asked for, and each one
 * points at a PMS package that already exists, so the reminder has something
 * real to recommend rather than a vague "come in sometime".
 *
 * Intervals follow the usual owner's-manual guidance for Philippine city
 * driving: 5,000 km or six months on engine oil, whichever comes first.
 */
class MaintenanceRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Matched on name rather than id: the ids differ between the local
        // SQLite file and production. A package that does not exist yet leaves
        // the rule advisory — it still reminds, it just names itself.
        $bundles = Bundle::pluck('id', 'name');

        $rules = [
            [
                'name'            => 'Basic PMS due',
                'description'     => 'Engine oil, oil filter and a general inspection.',
                'bundle'          => 'Basic Service',
                'interval_months' => 6,
                'interval_km'     => 5000,
                'lead_days'       => 14,
                'sort_order'      => 10,
            ],
            [
                'name'            => 'Standard PMS due',
                'description'     => 'Basic PMS plus air and fuel filters, and a brake inspection.',
                'bundle'          => 'Standard Service',
                'interval_months' => 12,
                'interval_km'     => 10000,
                'lead_days'       => 21,
                'sort_order'      => 20,
            ],
            [
                'name'            => 'Major service due',
                'description'     => 'Full service including transmission fluid and coolant.',
                'bundle'          => 'Major Service',
                'interval_months' => 24,
                'interval_km'     => 40000,
                'lead_days'       => 30,
                'sort_order'      => 30,
            ],
        ];

        foreach ($rules as $rule) {
            $bundleName = $rule['bundle'];
            unset($rule['bundle']);

            // Create what is missing, never touch what is there. The entrypoint
            // runs this on every container start, and an interval the shop has
            // tuned — or deliberately switched off — must survive a restart.
            if (MaintenanceRule::where('name', $rule['name'])->exists()) {
                $this->command?->info("Interval '{$rule['name']}' already exists — left as the shop has it.");

                continue;
            }

            $rule['bundle_id']    = $bundles[$bundleName] ?? null;
            $rule['vehicle_type'] = null;
            $rule['is_active']    = true;

            MaintenanceRule::create($rule);

            $this->command?->info(
                "Interval '{$rule['name']}' created"
                .($rule['bundle_id'] ? " (recommends {$bundleName})" : ' (no package linked yet)')
            );
        }
    }
}
