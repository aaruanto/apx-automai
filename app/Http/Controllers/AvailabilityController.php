<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\BookingAvailability;
use Illuminate\Http\Request;

/**
 * Public JSON endpoint backing the two-panel availability picker. It only
 * exposes remaining-slot counts (never customer data), so it's safe to leave
 * open to guests on the landing page.
 */
class AvailabilityController extends Controller
{
    public function __invoke(Request $request, BookingAvailability $availability)
    {
        $request->validate([
            'service_ids'   => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:services,id',
            'year'          => 'nullable|integer|min:2020|max:2100',
            'month'         => 'nullable|integer|min:1|max:12',
            'date'          => 'nullable|date',
        ]);

        $duration = $availability->durationForServices($request->service_ids);

        if ($duration < 1) {
            return response()->json(['message' => 'Selected service(s) could not be found.'], 422);
        }

        $response = ['duration' => $duration];

        if ($request->filled('date')) {
            $response['date']  = $request->date('date')->toDateString();
            $response['slots'] = $availability->dayAvailability($response['date'], $duration);
        }

        if ($request->filled('year') && $request->filled('month')) {
            $response['year']  = (int) $request->year;
            $response['month'] = (int) $request->month;
            $response['days']  = $availability->monthAvailability($response['year'], $response['month'], $duration);
        }

        if (! isset($response['slots']) && ! isset($response['days'])) {
            return response()->json(['message' => 'Pass either a date, or a year and month.'], 422);
        }

        return response()->json($response);
    }
}
