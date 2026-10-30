<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Splits vehicles whose make holds the whole name into make and model.
 *
 * The customer dashboard used to post "Honda Civic 2023" as the make with the
 * model hardcoded empty, so rows written that way carry the manufacturer and
 * the model line in one column. Every screen that reads vehicles.model shows
 * nothing for them — which is how this was noticed.
 *
 * The forms are fixed, so nothing new arrives in that shape. This is only for
 * rows already stored.
 *
 * It reports by default and writes nothing. --apply is the only way to change
 * a row, and it is deliberately not scheduled and not called from anywhere:
 * someone has to decide to run it, having read what it would do.
 *
 * Splitting is done against config/vehicles.php rather than by guessing at
 * spaces. "Mirage G4" and "Click 125i" are two words of model, and a rule like
 * "first word is the make" would file a Mitsubishi Mirage G4 as a Mitsubishi
 * Mirage with G4 left over. A row whose make does not begin with a manufacturer
 * the catalogue knows is reported and left alone, because a wrong split is
 * worse than the current blank: blank is visibly missing, wrong looks correct.
 */
class SplitVehicleMakeModel extends Command
{
    protected $signature = 'vehicles:split-make-model {--apply : Write the changes. Without this, nothing is modified.}';

    protected $description = 'Report (or, with --apply, fix) vehicles whose make column holds the whole name';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // Soft-deleted rows included: they keep their plate and can be
        // restored, so leaving them half-migrated just defers the problem.
        $candidates = Vehicle::withTrashed()
            ->where(fn ($q) => $q->whereNull('model')->orWhere('model', ''))
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nothing to split: every vehicle already has a model.');

            return self::SUCCESS;
        }

        $makes      = $this->knownMakes();
        $resolved   = [];
        $unresolved = [];

        foreach ($candidates as $vehicle) {
            $split = $this->split((string) $vehicle->make, $makes);

            $split ? $resolved[] = [$vehicle, $split] : $unresolved[] = $vehicle;
        }

        $this->table(
            ['id', 'plate', 'make (now)', 'make (after)', 'model (after)'],
            collect($resolved)->map(fn ($r) => [
                $r[0]->id, $r[0]->plate_number, $r[0]->make, $r[1][0], $r[1][1],
            ])->all()
        );

        if ($unresolved) {
            $this->newLine();
            $this->warn('Left alone — no manufacturer in config/vehicles.php matches the start of these:');

            foreach ($unresolved as $vehicle) {
                $this->line(sprintf('  #%-4s %-14s %s', $vehicle->id, $vehicle->plate_number, '['.$vehicle->make.']'));
            }

            $this->line('  A wrong split reads as correct, so these want a human. Fix them from');
            $this->line('  the admin customer screen, or add the manufacturer to config/vehicles.php.');
        }

        $this->newLine();
        $this->line(sprintf('%d splittable, %d left alone, %d examined.',
            count($resolved), count($unresolved), $candidates->count()));

        if (! $apply) {
            $this->newLine();
            $this->info('Dry run. Nothing was written. Re-run with --apply to make these changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($resolved) {
            foreach ($resolved as [$vehicle, [$make, $model]]) {
                $vehicle->forceFill(['make' => $make, 'model' => $model])->save();
            }
        });

        $this->newLine();
        $this->info(sprintf('Applied: %d vehicle(s) split.', count($resolved)));

        return self::SUCCESS;
    }

    /** Every manufacturer the catalogue knows, longest first. */
    private function knownMakes(): array
    {
        $makes = collect(config('vehicles', []))
            ->flatMap(fn ($byMake) => array_keys($byMake))
            ->unique()
            ->values()
            ->all();

        // Longest first, so a manufacturer whose name starts with another's is
        // still matched whole.
        usort($makes, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $makes;
    }

    /**
     * "Honda Civic 2023" => ['Honda', 'Civic 2023'], or null when the string
     * does not begin with a manufacturer we recognise.
     *
     * @return array{0: string, 1: string}|null
     */
    private function split(string $whole, array $makes): ?array
    {
        $whole = trim(preg_replace('/\s+/', ' ', $whole));

        foreach ($makes as $make) {
            if (strcasecmp($whole, $make) === 0) {
                // Just the manufacturer, nothing to move.
                return null;
            }

            if (stripos($whole, $make . ' ') === 0) {
                return [$make, trim(substr($whole, strlen($make) + 1))];
            }
        }

        return null;
    }
}
