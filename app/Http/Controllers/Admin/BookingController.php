<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\User;
use App\Notifications\BookingNotification;
use App\Models\Service;
use App\Mail\BookingConfirmed;
use App\Services\BookingAvailability;
use App\Services\LoyaltyService;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Support\CsvExport;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'service', 'vehicle', 'mechanic', 'cancelledBy', 'services'])
                        ->latest()
                        ->get();

        $counts = [
            'confirmed'   => $bookings->where('status', 'confirmed')->count(),
            'pending'     => $bookings->where('status', 'pending')->count(),
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'cancelled'   => $bookings->where('status', 'cancelled')->count(),
        ];

        $services = Service::orderBy('name')->get();

        $mechanics = Mechanic::active()->get();

        return view('admin.bookings.index', compact('bookings', 'counts', 'services', 'mechanics'));
    }

    public function create()
    {
        $services  = Service::orderBy('name')->get();
        $mechanics = Mechanic::active()->get();

        return view('admin.bookings.create', compact('services', 'mechanics'));
    }

    // ── FIX #2: real validation + one shared availability gate ───────────────
    public function store(Request $request)
    {
        // Accept a single service_id too, so anything still posting the old
        // shape keeps working, same as the customer and edit paths.
        if (! $request->filled('service_ids') && $request->filled('service_id')) {
            $request->merge(['service_ids' => [$request->input('service_id')]]);
        }

        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            // The same rule the customer's own vehicle form and the guest
            // booking use. This path accepted anything up to 20 characters,
            // so the admin could create a vehicle the customer could never
            // then edit, because their form would reject its plate.
            'plate'          => ['required', 'string', 'max:20', 'regex:/^[A-Z]{3} \d{3,4}$/i'],
            'service_ids'    => 'required|array|min:1',
            'service_ids.*'  => 'integer|exists:services,id',
            'booking_date'   => 'required|date|after_or_equal:today', // no past dates
            'booking_time'   => 'required',
            'mechanic_id'       => ['nullable', 'integer', Rule::exists('mechanics', 'id')->where('status', 'active')],
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
                'service_ids'  => $request->service_ids,
                'mechanic_id'     => $request->mechanic_id ?? null,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'status'       => 'pending',
                'notes'        => $request->notes,
            ]);
        } catch (SlotUnavailableException $e) {
            return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
        }

        $this->notifyAdminsOfBooking($booking);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking #' . $booking->reference_number . ' created successfully.');
    }

    public function edit($id)
    {
        $booking   = Booking::with(['user', 'vehicle', 'service', 'mechanic', 'services'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $mechanics = Mechanic::active()->get();

        // The trail for this booking. Shown on the page it describes, because
        // "who changed this, and when" is asked while looking at the booking,
        // not while hunting through a separate log.
        $history = AuditLog::where('auditable_type', Booking::class)
            ->where('auditable_id', $booking->id)
            ->recentFirst()->limit(20)->get();

        return view('admin.bookings.create', compact('booking', 'services', 'mechanics', 'history'));
    }

    public function update(Request $request, $id)
    {
        $booking   = Booking::findOrFail($id);
        $oldStatus = $booking->status;
        $oldDate   = $booking->booking_date;
        $oldTime   = $booking->booking_time;

        if (! $request->filled('service_ids') && $request->filled('service_id')) {
            $request->merge(['service_ids' => [$request->input('service_id')]]);
        }

        $request->validate([
            'service_ids'   => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:services,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            // 'cancelled' is deliberately absent. Reaching it through this
            // dropdown skipped the required-reason flow entirely, which is the
            // whole point of the cancel action. Use that instead.
            'status'       => 'required|in:pending,confirmed,in_progress,completed',
            'mechanic_id'     => ['nullable', 'integer', Rule::exists('mechanics', 'id')->where('status', 'active')],
            'notes'        => 'nullable|string|max:2000',
        ]);

        // A completed booking is a record of work done. Its status, services
        // and slot are frozen; only the notes stay open, because an admin
        // still needs somewhere to write what happened afterwards. Without
        // this, saving the form re-priced finished work at today's rates and
        // the revenue reports moved underneath you.
        if ($booking->isLocked()) {
            $booking->update(['notes' => $request->notes]);

            return redirect()->route('admin.bookings.index')->with(
                'success',
                'Notes saved. '.$booking->reference_number.' is completed, so its status, '
                .'services and schedule are locked to keep the record and the revenue figures intact.'
            );
        }

        // Forward only. Cancelling is the one way out and has its own action,
        // because it requires a reason.
        if ($request->status !== $oldStatus && ! $booking->canMoveTo($request->status)) {
            return back()->withInput()->withErrors([
                'status' => 'A booking cannot move from '.(self::STATUS_LABELS[$oldStatus] ?? $oldStatus)
                            .' back to '.(self::STATUS_LABELS[$request->status] ?? $request->status)
                            .'. Cancel it instead if the work is not going ahead.',
            ]);
        }

        // Only re-run the capacity check when the slot this booking occupies
        // actually changes — a pure status/notes edit shouldn't get blocked
        // by the booking's own existing slot.
        $availability = app(BookingAvailability::class);

        $serviceIds = array_values(array_unique(array_map('intval', $request->service_ids)));

        // Compare as sets: a different order is not a different booking.
        $sortedNew  = $serviceIds; sort($sortedNew);
        $sortedOld  = $booking->services()->pluck('services.id')->map('intval')->sort()->values()->all();
        $servicesMoved = $sortedNew !== $sortedOld;

        // Changing which services are on the booking changes how long it
        // occupies the bay, so the slot has to be re-checked for that too.
        $rescheduled = $servicesMoved
            || $request->booking_date !== $booking->booking_date
            || $request->booking_time !== $booking->booking_time;

        $duration = $booking->duration;

        if ($rescheduled) {
            $duration = $availability->durationForServices($serviceIds);

            try {
                $availability->reschedule(
                    $booking, $request->booking_date, $request->booking_time, $duration
                );
            } catch (SlotUnavailableException $e) {
                return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
            }
        }

        // Same overlap rule as capacity: one mechanic cannot be in two bays at
        // once. Blocked here rather than warned, because the edit form is the
        // deliberate path; the quick Assign control offers an override.
        $newMechanicId = $request->mechanic_id ?? $booking->mechanic_id;

        if ($newMechanicId) {
            [$winStart, $winEnd] = $availability->windowFor(
                $request->booking_date, $request->booking_time, $duration
            );

            $clash = $availability->mechanicConflict(
                $newMechanicId, $request->booking_date, $winStart, $winEnd, $booking->id
            );

            if ($clash) {
                return back()->withInput()->withErrors([
                    'mechanic_id' => 'That mechanic is already on '.$clash->reference_number
                                  .' at '.Carbon::parse($clash->booking_time)->format('g:i A')
                                  .' that day. Choose another, or change the time.',
                ]);
            }
        }
        $booking->update([
            // Kept pointing at the first selection for screens still reading
            // a single service.
            'service_id'   => $serviceIds[0],
            'mechanic_id'     => $request->mechanic_id ?? $booking->mechanic_id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
            'duration'     => $duration,
            'status'       => $request->status,
            'notes'        => $request->notes,
        ]);

        // Keep the price each service already carried on this booking; only a
        // newly added service is quoted at today's rate.
        $availability->attachServices($booking, $serviceIds, preserveExisting: true);

        // ── Audit: the changes that move money ─────────────────────────────
        if ($request->status !== $oldStatus) {
            AuditLog::record(
                'booking.status_changed', $booking,
                ['status' => $oldStatus], ['status' => $request->status],
                label: $booking->reference_number,
            );
        }

        if ($servicesMoved) {
            AuditLog::record(
                'booking.services_changed', $booking,
                ['service_ids' => $sortedOld], ['service_ids' => $sortedNew],
                label: $booking->reference_number,
            );
        }

        // Points are earned when the work is done, never when it is booked,
        // and on the amount actually paid so a discount is not rewarded
        // twice. earnFor is idempotent, so this cannot mint on a re-save.
        if ($oldStatus !== 'completed' && $request->status === 'completed') {
            app(LoyaltyService::class)->earnFor($booking->fresh());
        }

        if ($oldDate !== $booking->booking_date || $oldTime !== $booking->booking_time) {
            AuditLog::record(
                'booking.rescheduled', $booking,
                ['booking_date' => $oldDate, 'booking_time' => $oldTime],
                ['booking_date' => $booking->booking_date, 'booking_time' => $booking->booking_time],
                label: $booking->reference_number,
            );
        }

        // Tell the customer what changed. Only on a real transition: saving
        // the form without touching the status or the slot should not notify.
        if ($booking->user) {
            if ($oldStatus !== 'confirmed' && $request->status === 'confirmed') {
                $booking->user->notify(BookingNotification::confirmed($booking->fresh()));
            }

            if ($oldStatus !== 'completed' && $request->status === 'completed') {
                $booking->user->notify(BookingNotification::completed($booking->fresh()));
            }

            if ($oldDate !== $booking->booking_date || $oldTime !== $booking->booking_time) {
                $booking->user->notify(
                    BookingNotification::rescheduled($booking->fresh(), $oldDate, $oldTime)
                );
            }
        }

        // The plate is deliberately not editable here. This form used to write
        // $request->plate straight onto the Vehicle with no validation at all,
        // while the customer's own vehicle form enforces the LTO format and
        // uniqueness — so "nonsense!!!" was accepted. It also wrote to the
        // shared Vehicle row, changing the plate on every other booking for
        // that car. A plate belongs to the vehicle, not to one booking.

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
        $bookings = Booking::with(['user', 'service', 'vehicle', 'services'])
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
        $cancelled = Booking::with(['user', 'service', 'vehicle', 'cancelledBy', 'services'])
                        ->where('status', 'cancelled')
                        ->latest()
                        ->get();

        $services = Service::orderBy('name')->get();

        return view('admin.bookings.cancelled', compact('cancelled', 'services'));
    }

    public function rebook($id)
    {
        $original  = Booking::with(['user', 'vehicle', 'service', 'mechanic', 'services'])->findOrFail($id);
        $services  = Service::orderBy('name')->get();
        $mechanics = Mechanic::active()->get();

        return view('admin.bookings.create', [
            'services'  => $services,
            'mechanics' => $mechanics,
            'prefill'   => $original,
        ]);
    }

    /**
     * Delete a booking.
     *
     * Used to be a bare delete() with no reason, no status guard and no
     * record of who did it — the soft underbelly next to cancel, which has
     * demanded a reason all along. Deleting a completed booking removed its
     * revenue from every report with nothing left to explain the gap.
     */
    public function destroy(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => $booking->reference_number.' is completed and cannot be deleted. '
                             .'Its revenue is part of your reports.',
            ], 422);
        }

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'Give a reason for deleting this booking.',
            'reason.min'      => 'Give a little more detail than that.',
        ]);

        AuditLog::record(
            'booking.deleted', $booking,
            ['status' => $booking->status, 'booking_date' => $booking->booking_date],
            [], $data['reason'],
            label: $booking->reference_number,
        );

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => $booking->reference_number.' deleted.',
        ]);
    }

    /**
     * Assign (or clear) the mechanic on a booking without opening the full
     * edit form.
     *
     * Warns rather than blocks: a clash returns 409 with the clashing booking
     * named, and the caller may repeat the request with force to accept it.
     * Double-booking is sometimes deliberate, but it should never be silent.
     */
    public function assignMechanic(Request $request, $id)
    {
        $data = $request->validate([
            'mechanic_id' => ['nullable', 'integer', Rule::exists('mechanics', 'id')->where('status', 'active')],
            'force'    => ['nullable', 'boolean'],
        ], [
            'mechanic_id.exists' => 'That mechanic is no longer on the active list.',
        ]);

        $booking  = Booking::findOrFail($id);
        $mechanicId  = $data['mechanic_id'] ?? null;

        if ($mechanicId && empty($data['force'])) {
            $availability = app(BookingAvailability::class);
            [$start, $end] = $availability->windowFor(
                $booking->booking_date,
                $booking->booking_time,
                $booking->duration ?? $availability->gridMinutes()
            );

            $clash = $availability->mechanicConflict(
                $mechanicId, $booking->booking_date, $start, $end, $booking->id
            );

            if ($clash) {
                return response()->json([
                    'success'  => false,
                    'conflict' => true,
                    'message'  => 'That mechanic is already on '.$clash->reference_number
                                  .' at '.Carbon::parse($clash->booking_time)->format('g:i A')
                                  .' on the same day. Assign anyway?',
                ], 409);
            }
        }

        $booking->update(['mechanic_id' => $mechanicId]);
        $booking->load('mechanic');

        return response()->json([
            'success'     => true,
            'mechanic_id'    => $mechanicId,
            'mechanic_name'  => $booking->mechanic->name ?? 'Unassigned',
            'message'     => $mechanicId
                ? $booking->mechanic->name.' is now assigned to '.$booking->reference_number.'.'
                : 'Mechanic cleared from '.$booking->reference_number.'.',
        ]);
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

        // Cancellation already stamped who and why on the booking itself; it
        // joins the audit trail too so one page shows every change that moved
        // money, rather than this one living somewhere of its own.
        AuditLog::record(
            'booking.cancelled', $booking,
            ['status' => 'cancelled'], [], $validated['reason'],
            label: $booking->reference_number,
        );

        // Work that is not happening earns nothing. Points spent on it are
        // returned, and points it earned are taken back — clamped so a
        // customer is never pushed into a negative balance by the shop's
        // own cancellation.
        $loyalty = app(LoyaltyService::class);
        $loyalty->reverseFor($booking);
        $loyalty->refundSpentFor($booking);

        if ($booking->user) {
            $booking->user->notify(BookingNotification::cancelled($booking->fresh()));
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
     * console.error'd, so nothing appeared at all and the button looked dead.
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


        // Re-check the status inside the write so two quick clicks (or two tabs
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
            ['Reference', 'Customer', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
            $this->bookingRows(Booking::with(['user', 'service', 'vehicle', 'services'])->latest()->cursor())
        );
    }

    /** CSV of cancelled bookings, matching the Cancelled screen. */
    public function exportCancelled()
    {
        return CsvExport::stream(
            CsvExport::filename('cancelled-bookings'),
            ['Reference', 'Customer', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
            $this->bookingRows(
                Booking::with(['user', 'service', 'vehicle', 'services'])->where('status', 'cancelled')->latest()->cursor()
            )
        );
    }

    /** Shared row shape, generator so rows stream instead of all loading. */
    private function bookingRows(iterable $bookings): iterable
    {
        foreach ($bookings as $b) {
            // Email, phone, vehicle and plate are deliberately absent: an
            // exported file is the easiest thing to mislay, and none of them
            // are needed to read a booking list.
            //
            // service_list rather than service->name, which showed only the
            // first service once a booking could carry several.
            yield [
                $b->reference_number,
                $b->user->name ?? 'N/A',
                $b->service_list,
                $b->booking_date,
                $b->booking_time,
                ucfirst(str_replace('_', ' ', $b->status)),
                optional($b->created_at)->format('Y-m-d H:i'),
            ];
        }
    }

    /**
     * Alert every admin that a booking has come in.
     *
     * Only the admin is notified: mechanics have no login to be notified in, and
     * notifying every mechanic for every booking would be noise.
     */
    private function notifyAdminsOfBooking(\App\Models\Booking $booking): void
    {
        $admins = \App\Models\User::where('role', 'admin')->get();

        \Illuminate\Support\Facades\Notification::send(
            $admins,
            \App\Notifications\BookingNotification::requested($booking->fresh()->load(['user', 'services']))
        );
    }}
