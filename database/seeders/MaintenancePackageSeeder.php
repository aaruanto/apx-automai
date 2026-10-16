<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * The maintenance packages the guided booking flow offers.
 *
 * These exist because the Periodic Maintenance path is only as good as what
 * sits behind it: a customer who cannot name the services their vehicle is
 * due for needs a package that says "Standard Service", not a list they would
 * have to assemble themselves.
 *
 * Named Basic / Standard / Major rather than by mileage on purpose. A
 * customer who does not know their odometer reading — a very ordinary thing
 * not to know while sitting at home — can still tell which of three is
 * bigger. Asking for mileage would put a question they cannot answer between
 * them and a booking, which is the problem this whole screen exists to solve.
 *
 * Safe to re-run: packages are matched by name and updated rather than
 * duplicated, and nothing is deleted. Prices are a starting point for the
 * shop to adjust in Admin -> Bundles, not a fixed decision.
 */
class MaintenancePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'        => 'Basic Service',
                'description' => 'The routine oil change, with a quick once-over while your vehicle is in.',
                'price'       => 1350,
                'services'    => ['Change Oil (Gasoline)', 'Tire Pressure & Fluid Top-up', 'Free Vehicle Health Check'],
            ],
            [
                'name'        => 'Standard Service',
                'description' => 'Oil, engine flush and brake fluid, plus a full inspection. The usual mid-interval service.',
                'price'       => 3400,
                'services'    => ['Change Oil (Gasoline)', 'Engine Flush', 'Brake Fluid Flush', 'General Vehicle Inspection'],
            ],
            [
                'name'        => 'Major Service',
                'description' => 'The full service: oil, tune-up, transmission fluid and brakes. For longer intervals.',
                'price'       => 8200,
                'services'    => ['Change Oil (Gasoline)', 'Engine Tune-Up', 'CVT Fluid Change', 'Brake Fluid Flush'],
            ],
        ];

        foreach ($packages as $package) {
            $services = Service::whereIn('name', $package['services'])->get();

            // A package missing its services would be a bundle of nothing,
            // and the booking flow would offer an empty card. Skip it and say
            // so rather than creating something broken.
            if ($services->count() < 2) {
                $this->command?->warn(
                    "Skipped '{$package['name']}': needs at least two of its services to exist. "
                    .'Found '.$services->count().' of '.count($package['services']).'.'
                );

                continue;
            }

            $listPrice = (float) $services->sum('price');

            // The admin form refuses a package that costs as much as its
            // parts, and so should this: a package that saves nothing is
            // just a longer way to buy the same services.
            if ($package['price'] >= $listPrice) {
                $this->command?->warn(
                    "Skipped '{$package['name']}': ₱{$package['price']} is not below the "
                    .'₱'.number_format($listPrice, 2).' these services cost separately.'
                );

                continue;
            }

            $bundle = Bundle::updateOrCreate(
                ['name' => $package['name']],
                [
                    'description' => $package['description'],
                    'price'       => $package['price'],
                    'is_active'   => true,
                ]
            );

            $bundle->services()->sync($services->pluck('id')->all());

            $this->command?->info(sprintf(
                '%s — ₱%s (separately ₱%s, saves ₱%s)',
                $package['name'],
                number_format($package['price'], 2),
                number_format($listPrice, 2),
                number_format($listPrice - $package['price'], 2),
            ));
        }
    }
}
