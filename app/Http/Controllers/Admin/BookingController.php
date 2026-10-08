<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Employee;
use App\Mail\BookingConfirmed;
use App\Services\BookingAvailability;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Support\CsvExport;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'service', 'vehicle', 'employee', 'cancelledBy'])
                        ->latest()
                        ->get();

        $counts = [
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        $services = Service::orderBy('name')->get();

        return view('admin.bookings.index', compact('bookings', 'counts', 'services'));
    }

    public function create()
    {
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', compact('services', 'employees'));
    }

    // ── FIX #2: real validation + one shared availability gate ───────────────
    public function store(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'plate'          => 'required|string|max:20',
            'service_id'     => 'required|exists:services,id',
            'booking_date'   => 'required|date|after_or_equal:today', // no past dates
            'booking_time'   => 'required',
        ]);

        $user = \App\Models\User::firstOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name'     => $request->customer_name,
                'email'    => $request->customer_email
                              ?? strtolower(str_replace(' ', '', $request->customer_name)) . '@apxautomai.local',
                'role'     => 'customer',
                // A literal "password" here meant anyone who worked out the
                // email pattern could sign in as that customer. Random and
                // never displayed: the account carries bookings but cannot be
                // logged into until the customer claims it by registering with
                // this email, or an admin resets it. Matches what
                // GuestBookingController already does.
                'password' => Str::password(32),
            ]
        );

        $vehicle = $user->vehicles()->firstOrCreate(
            ['plate_number' => strtoupper($request->plate)],
            [
                'make'  => $request->car_model ?? 'Unknown',
                'model' => $request->car_model ?? '',
                'year'  => null,
            ]
        );

        try {
            $booking = app(BookingAvailability::class)->reserve([
                'user_id'      => $user->id,
                'vehicle_id'   => $vehicle->id,
                'service_id'   => $request->service_id,
                'staff_id'     => $request->staff_id ?? null,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'status'       => 'pending',
                'notes'        => $request->notes,
            ]);
        } catch (SlotUnavailableException $e) {
            return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking #' . $booking->reference_number . ' created successfully.');
    }

    public function edit($id)
    {
        $booking   = Booking::with(['user', 'vehicle', 'service', 'employee'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', compact('booking', 'services', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $booking   = Booking::findOrFail($id);
        $oldStatus = $booking->status;

        $request->validate([
            'service_id'   => 'required|exists:services,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            // 'cancelled' is deliberately absent. Reaching it through this
            // dropdown skipped the required-reason flow entirely, which is the
            // whole point of the cancel action. Use that instead.
            'status'       => 'required|in:pending,confirmed,in_progress,completed',
        ]);

        // Only re-run the capacity check when the slot this booking occupies
        // actually changes — a pure status/notes edit shouldn't get blocked
        // by the booking's own existing slot.
        $rescheduled = $request->service_id != $booking->service_id
            || $request->booking_date !== $booking->booking_date
            || $request->booking_time !== $booking->booking_time;

        $duration = $booking->duration;

        if ($rescheduled) {
            $duration = Service::find($request->service_id)->duration;

            try {
                app(BookingAvailability::class)->reschedule(
                    $booking, $request->booking_date, $request->booking_time, $duration
                );
            } catch (SlotUnavailableException $e) {
                return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
            }
        }

        $booking->update([
            'service_id'   => $request->service_id,
            'staff_id'     => $request->staff_id ?? $booking->staff_id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
            'duration'     => $duration,
            'status'       => $request->status,
            'notes'        => $request->notes,
        ]);

        if ($request->plate && $booking->vehicle) {
            $booking->vehicle->update([
                'plate_number' => strtoupper($request->plate),
                'model'        => $request->car_model ?? $booking->vehicle->model,
            ]);
        }

        // Send confirmation email when status changes to 'confirmed'
        if ($oldStatus !== 'confirmed' && $request->status === 'confirmed') {
            $booking->load(['user', 'service', 'vehicle']);
            if (
                $booking->user &&
                $booking->user->email &&
                !str_ends_with($booking->user->email, '@apxautomai.local')
            ) {
                try {
                    Mail::to($booking->user->email)->send(new BookingConfirmed($booking));
                } catch (\Exception $e) {
                    \Log::error('Booking confirmation email failed: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    public function schedule()
    {
        $bookings = Booking::with(['user', 'service', 'vehicle'])
                        ->whereDate('booking_date', today())
                        ->orderBy('booking_time')
                        ->get();

        $totalToday = $bookings->count();

        $statuses = [
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        return view('admin.bookings.schedule', compact('bookings', 'totalToday', 'statuses'));
    }

    public function cancelled()
    {
        $cancelled = Booking::with(['user', 'service', 'vehicle', 'cancelledBy'])
                        ->where('status', 'cancelled')
                        ->latest()
                        ->get();

        $services = Service::orderBy('name')->get();

        return view('admin.bookings.cancelled', compact('cancelled', 'services'));
    }

    public function rebook($id)
    {
        $original  = Booking::with(['user', 'vehicle', 'service', 'employee'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.create', [
            'services'  => $services,
            'employees' => $employees,
            'prefill'   => $original,
        ]);
    }

    public function destroy($id)
    {
        Booking::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Cancel a booking, with a reason on the record.
     *
     * The reason is required because this is the accountability trail the
     * panel asked for: a cancelled booking should always say who cancelled it
     * and why. Nothing else about the booking is touched.
     */
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'Please give a reason for cancelling this booking.',
            'reason.min'      => 'Please give a little more detail than that.',
        ]);

        $booking = Booking::findOrFail($id);

        if (! $booking->cancel($validated['reason'], $request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is '.(self::STATUS_LABELS[$booking->status] ?? $booking->status)
                             .' and can no longer be cancelled.',
            ], 422);
        }

        return response()->json([
            'success'      => true,
            'status'       => 'cancelled',
            'status_label' => 'Cancelled',
            'message'      => 'Booking '.$booking->reference_number.' has been cancelled.',
            'cancel_reason' => $booking->cancel_reason,
            'cancelled_by'  => $request->user()->name,
            'cancelled_at'  => $booking->cancelled_at?->format('M j, Y g:i A'),
        ]);
    }

    private const STATUS_LABELS = [
        'pending'     => 'Pending',
        'confirmed'   => 'Confirmed',
        'in_progress' => 'already in progress',
        'completed'   => 'already completed',
        'cancelled'   => 'cancelled',
    ];

    /**
     * Mark a booking "Arrived" and start the service — in_progress protects it
     * from the no-show auto-cancel job.
     *
     * This previously gated status with whereIn(...)->findOrFail(), which did
     * block invalid transitions but surfaced them as a 404 with an HTML body.
     * The caller's r.json() then threw on that HTML and the rejection was only
     * console.error'd, so staff saw nothing at all and the button looked dead.
     * Refused transitions now return 422 with a message the UI can display.
     */
    public function arrive($id)
    {
        $booking = Booking::findOrFail($id);

        if (! in_array($booking->status, Booking::STARTABLE_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is '.(self::STATUS_LABELS[$booking->status] ?? $booking->status)
                             .'. Only pending or confirmed bookings can be started.',
            ], 422);
        }


        // Re-check the status inside the write so two quick clicks (or two staff
        // on the same booking) can't both pass the guard above and double-stamp
        // arrived_at. 0 rows means someone else got there first.
        $started = Booking::where('id', $booking->id)
            ->whereIn('status', Booking::STARTABLE_STATUSES)
            ->update([
                'status'     => 'in_progress',
                'arrived_at' => now(),
            ]);

        if ($started === 0) {
            return response()->json([
                'success' => false,
                'message' => 'This booking was just updated by someone else. Refresh to see its current status.',
            ], 409);
        }

        return response()->json([
            'success'      => true,
            'status'       => 'in_progress',
            'status_label' => 'In Progress',
            'message'      => 'Service started for '.$booking->reference_number.'.',
        ]);
    }
    /**
     * CSV of every booking. Exports the full table rather than whatever the
     * page's client-side filters happen to show, so the file is reproducible
     * and does not depend on UI state the server never sees.
     */
    public function export()
    {
        return CsvExport::stream(
            CsvExport::filename('bookings'),
            ['Reference', 'Customer', 'Email', 'Phone', 'Vehicle', 'Plate', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
            $this->bookingRows(Booking::with(['user', 'service', 'vehicle'])->latest()->cursor())
        );
    }

    /** CSV of cancelled bookings, matching the Cancelled screen. */
    public function exportCancelled()
    {
        return CsvExport::stream(
            CsvExport::filename('cancelled-bookings'),
            ['Reference', 'Customer', 'Email', 'Phone', 'Vehicle', 'Plate', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
            $this->bookingRows(
                Booking::with(['user', 'service', 'vehicle'])->where('status', 'cancelled')->latest()->cursor()
            )
        );
    }

    /** Shared row shape, generator so rows stream instead of all loading. */
    private function bookingRows(iterable $bookings): iterable
    {
        foreach ($bookings as $b) {
            yield [
                $b->reference_number,
                $b->user->name ?? 'N/A',
                $b->user->email ?? '',
                $b->user->phone ?? '',
                $b->vehicle?->display_name ?? '',
                $b->vehicle?->display_plate ?? 'Not provided',
                $b->service->name ?? '',
                $b->booking_date,
                $b->booking_time,
                ucfirst(str_replace('_', ' ', $b->status)),
                optional($b->created_at)->format('Y-m-d H:i'),
            ];
        }
    }
}