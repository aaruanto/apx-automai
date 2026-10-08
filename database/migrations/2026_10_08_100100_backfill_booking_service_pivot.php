<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move existing bookings into the pivot before anything starts reading it, so
 * no booking silently loses its service.
 *
 * Two sources:
 *  1. bookings.service_id, which every booking has.
 *  2. Guest bookings already accepted several services but could only store
 *     one, so the rest were appended to the notes field as
 *     "[Additional services: A, B]". Those are recovered into real rows here
 *     and the marker stripped from the note.
 */
return new class extends Migration
{
    private const MARKER = '/\s*\[Additional services:\s*(.+?)\]/s';

    public function up(): void
    {
        if (! Schema::hasTable('booking_service')) {
            return;
        }

        $services = DB::table('services')->pluck('id', 'name');          // name => id
        $byId     = DB::table('services')->select('id', 'price', 'duration')->get()->keyBy('id');
        $now      = now();

        DB::table('bookings')->orderBy('id')->chunkById(200, function ($bookings) use ($services, $byId, $now) {
            foreach ($bookings as $booking) {
                $ids = [];

                if ($booking->service_id) {
                    $ids[] = (int) $booking->service_id;
                }

                // Recover any services that were only ever recorded in the note.
                if (! empty($booking->notes) && preg_match(self::MARKER, $booking->notes, $m)) {
                    foreach (explode(',', $m[1]) as $name) {
                        $name = trim($name);
                        if ($name !== '' && isset($services[$name])) {
                            $ids[] = (int) $services[$name];
                        }
                    }

                    $cleaned = trim(preg_replace(self::MARKER, '', $booking->notes));

                    DB::table('bookings')->where('id', $booking->id)
                        ->update(['notes' => $cleaned === '' ? null : $cleaned]);
                }

                foreach (array_unique($ids) as $serviceId) {
                    $service = $byId[$serviceId] ?? null;
                    if (! $service) {
                        continue;
                    }

                    // Idempotent: re-running must not trip the unique index.
                    DB::table('booking_service')->updateOrInsert(
                        ['booking_id' => $booking->id, 'service_id' => $serviceId],
                        [
                            'price'      => $service->price,
                            'duration'   => $service->duration,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        // The note text cannot be reconstructed, and the pivot rows are
        // recreated from bookings.service_id by up(), so emptying it is enough.
        if (Schema::hasTable('booking_service')) {
            DB::table('booking_service')->delete();
        }
    }
};
