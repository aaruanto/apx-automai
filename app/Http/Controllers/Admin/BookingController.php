<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Services\BookingProgress;
use App\Support\EmailRule;
use App\Support\PlateNumber;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Mechanic;
use App\Models\RescheduleRequest;
use App\Models\Vehicle;
use App\Models\User;
use App\Notifications\BookingNotification;
use App\Models\Service;
use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Services\BookingAvailability;
use App\Services\BookingCancellation;
use App\Services\LoyaltyService;
use App\Services\SmsService;
use App\Support\MessageTemplate;
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

        // One count per status, generated from the enum rather than listed by
        // hand. The hand-written version had no entry for 'completed', so the
        // status cards above the table simply did not count finished work.
        $counts = collect(BookingStatus::cases())
            ->mapWithKeys(fn (BookingStatus $s) => [
                $s->value => $bookings->where('status', $s)->count(),
            ])
            ->all();

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

        // Normalised before validating, not after: the rules reject a leading,
        // trailing or doubled space, so cleaning has to happen first or a
        // customer typing "abc 1234 " is refused rather than tidied.
        $request->merge(['plate' => PlateNumber::normalise($request->input('plate'))]);

        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            // The same rule the customer's own vehicle form and the guest
            // booking use. This path accepted anything up to 20 characters,
            // so the admin could create a vehicle the customer could never
            // then edit, because their form would reject its plate.
            'plate'          => PlateNumber::rules(),
            'service_ids'    => 'required|array|min:1',
            'service_ids.*'  => 'integer|exists:services,id',
            'booking_date'   => 'required|date|after_or_equal:today', // no past dates
            'booking_time'   => 'required',
            'mechanic_id'       => ['nullable', 'integer', Rule::exists('mechanics', 'id')->where('status', 'active')],
            // Make and model are separate columns and this form posted one
            // string into both. Free text rather than a list: a walk-in can
            // arrive in anything, and this is the path that has to record it.
            'car_make'       => 'nullable|string|max:100',
            'car_model'      => 'nullable|string|max:100',
            'car_year'       => 'nullable|integer|min:1990|max:'.(date('Y') + 1),
            'car_type'       => 'nullable|in:car,motorcycle',
            // A walk-in may not know, or may not be asked, so nullable — but
            // the booking records whatever was said, because it decides which
            // gearbox services were legitimate to put on it.
            'car_transmission' => 'nullable|in:manual,automatic',

            // Was not validated at all — read only as a firstOrCreate default,
            // so a malformed address went into the users table and an address
            // already belonging to someone hit the unique index and returned a
            // 500. Not unique: a rebook legitimately posts the customer's own
            // existing address, and that must go through. The collision is
            // caught below, where it is known whether a row is being created.
            'customer_email' => ['nullable', EmailRule::strict()],

            // Who this booking is for, when the form already knows. The rebook
            // form posts both; a plain walk-in posts neither.
            'user_id'        => 'nullable|integer',
            'vehicle_id'     => 'nullable|integer',
        ]);

        // Who is this booking for?
        //
        // A rebook already knows: the cancelled booking it was opened from
        // names the customer and the vehicle, and the form now posts both. It
        // used to post neither, so the identity was re-derived from the typed
        // phone number — and any time that guess landed on a different row
        // than the vehicle's owner, the plate check below refused the booking
        // and named the customer the admin was already looking at.
        //
        // Three ways the guess went wrong, all three real in this data: an
        // account with no phone on record (10 of 16 users locally), the same
        // person existing two or three times under different identity keys
        // (three accounts are called "Gelo Ruanto"), and the admin correcting
        // a number in the form.
        //
        // Checked, not trusted: a posted id that names nobody falls back to
        // the old behaviour, so a forged value cannot attach a booking to a
        // stranger's account — it can only fail to help.
        $user = $request->filled('user_id')
            ? User::find((int) $request->user_id)
            : null;

        if (! $user) {
            $user = $this->resolveWalkInAccount($request);
        }

        $plate = PlateNumber::normalise($request->plate);

        // The vehicle, the same way. Honoured only when it really belongs to
        // this customer and still carries the plate on the form — so renaming
        // the plate on a rebook falls through to the lookup below and is
        // judged on its merits.
        $vehicle = null;

        if ($request->filled('vehicle_id')) {
            $vehicle = Vehicle::withTrashed()
                ->where('id', (int) $request->vehicle_id)
                ->where('user_id', $user->id)
                ->where('plate_number', $plate)
                ->first();

            if ($vehicle && $vehicle->trashed()) {
                $vehicle->restore();
            }
        }

        return $this->createBookingFor($request, $user, $vehicle);
    }

    /**
     * The account for a walk-in, resolved from what was typed.
     *
     * Keyed on the phone number, which is what this form has always done. It
     * is only reached when no usable user_id was posted — a plain walk-in, or
     * a rebook whose posted id named nobody.
     */
    private function resolveWalkInAccount(Request $request): User
    {
        $existing = User::where('phone', $request->customer_phone)->first();

        if ($existing) {
            return $existing;
        }

        // About to create. This is the only point at which a posted address
        // can collide with one already in the users table, and it used to do
        // so against the unique index — a 500 on the form with nothing saved.
        if ($request->filled('customer_email')
            && User::withTrashed()->where('email', $request->customer_email)->exists()) {
            abort(redirect()->back()->withInput()->withErrors([
                'customer_email' => 'That email address already belongs to another customer. '
                                    .'Leave it blank, or use the address on their existing account.',
            ]));
        }

        return User::create([
            'name'     => $request->customer_name,
            'phone'    => $request->customer_phone,
            'email'    => $request->customer_email
                          ?: $this->placeholderEmailFor($request->customer_name, $request->customer_phone),
            'role'     => 'customer',
            // A literal "password" here meant anyone who worked out the
            // email pattern could sign in as that customer. Random and
            // never displayed: the account carries bookings but cannot be
            // logged into until the customer claims it by registering with
            // this email, or an admin resets it. Matches what
            // GuestBookingController already does.
            'password' => Str::password(32),
        ]);
    }

    /**
     * Create the booking, once the customer and possibly the vehicle are known.
     *
     * $vehicle is already resolved only when the form named one that really
     * belongs to this customer and still carries the plate that was typed —
     * which is the rebook case. Everything else resolves the plate here, and
     * is refused if it belongs to somebody else.
     */
    private function createBookingFor(Request $request, User $user, ?Vehicle $vehicle)
    {
        $plate = PlateNumber::normalise($request->plate);

        // firstOrCreate scoped to this user asked a narrower question than the
        // database enforces: plate_number is globally unique, so a plate
        // registered to a different account is not free, and a soft-deleted
        // vehicle still holds its plate. Finding nothing, it inserted, the
        // constraint threw, and booking a walk-in whose plate was already in
        // the system returned a 500 with no booking created. Ask who holds it.
        $held = $vehicle ? null : Vehicle::holdingPlate($plate);

        if ($held && $held->user_id !== $user->id) {
            // Named, not merely refused. Admin can already see every customer
            // and vehicle in the portal, so this leaks nothing, and the useful
            // next step — book under the account that holds the plate —
            // requires knowing which account that is. The usual cause is the
            // same customer in the system twice under two phone numbers.
            $owner = $held->user?->name;

            return back()->withInput()->withErrors(['plate' => $owner
                ? "Plate {$plate} is already registered to {$owner}. Book under that customer, or correct the plate."
                : "Plate {$plate} is already registered to another account. Check the customer records, or correct the plate."]);
        }

        if ($held) {
            // Their own vehicle, deleted at some point. Restoring it keeps the
            // service history attached to the car rather than starting a second
            // record for it — which the unique index would refuse anyway.
            $held->restore();
            $vehicle = $held;
        } elseif (! $vehicle) {
            $vehicle = $user->vehicles()->create([
                'plate_number' => $plate,
                // One field called "Car Model" used to be written into both
                // columns, so a walk-in's vehicle read "Toyota Vios Toyota
                // Vios" wherever the two were shown together. Each column now
                // holds what its name says. make is NOT NULL, so it keeps a
                // fallback; the rest are honestly empty when not given.
                'make'         => $request->car_make ?: 'Unknown',
                'model'        => $request->car_model ?: '',
                'year'         => $request->car_year ?: null,
                'vehicle_type' => $request->car_type ?: 'car',
                // No fallback. 'car' is a safe guess for a type; neither
                // transmission is a safe guess for a gearbox.
                'transmission' => $request->car_transmission ?: null,
            ]);
        }

        // The counter learning something the record did not have.
        //
        // Fills a blank only, and never overwrites. Every vehicle registered
        // before the shop started asking has no transmission on file, and the
        // person standing at the counter with the car in front of them is the
        // best source there will ever be for it — but if the customer has
        // already set it from their own garage, that is theirs and stands.
        //
        // The same rule as the guest booking path, deliberately: a plate
        // arriving through either door should end up with the same record.
        if ($vehicle->transmission === null
            && in_array($request->car_transmission, ['manual', 'automatic'], true)) {
            $vehicle->update(['transmission' => $request->car_transmission]);
        }

        // Is the chosen mechanic already busy then?
        //
        // reserve() checks opening hours, shop capacity and the vehicle, and
        // never the mechanic — update() and assignMechanic() each ran this
        // check themselves, and store() ran neither. So creating a booking was
        // the one path in the application that could double-book a mechanic in
        // silence, which is also the path a rebook takes.
        //
        // Refused rather than offered, matching update(): both are form posts
        // that come back to a filled-in form, and the message names the
        // booking in the way so the admin can act on it. assignMechanic()
        // asks "assign anyway?" instead because it is a single deliberate
        // reassignment on an existing booking, not a new booking being made.
        if ($request->filled('mechanic_id')) {
            $availability = app(BookingAvailability::class);

            [$mechStart, $mechEnd] = $availability->windowFor(
                $request->booking_date,
                $request->booking_time,
                $availability->durationForServices((array) $request->service_ids)
                    ?: $availability->gridMinutes()
            );

            $clash = $availability->mechanicConflict(
                (int) $request->mechanic_id, $request->booking_date, $mechStart, $mechEnd
            );

            if ($clash) {
                return back()->withInput()->withErrors([
                    'mechanic_id' => 'That mechanic is already on '.$clash->reference_number
                                  .' at '.Carbon::parse($clash->booking_time)->format('g:i A')
                                  .' that day. Choose another, or change the time.',
                ]);
            }
        }

        try {
            $booking = app(BookingAvailability::class)->reserve([
                'user_id'      => $user->id,
                'vehicle_id'   => $vehicle->id,
                'service_ids'  => $request->service_ids,
                'mechanic_id'     => $request->mechanic_id ?? null,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'status'       => BookingStatus::PendingReview,
                'notes'        => $request->notes,
            ]);
        } catch (SlotUnavailableException $e) {
            return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
        }

        $this->recordCreation($booking, $request->input('rebooked_from'));

        $this->notifyAdminsOfBooking($booking);
        $this->notifyCustomerOfBooking($booking);

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

        // A schedule change the customer has not agreed to yet, if there is
        // one. The form below still shows the booking's real date and time —
        // the proposal is shown beside it, not instead of it.
        $pendingReschedule = $booking->pendingReschedule();

        return view('admin.bookings.create', compact(
            'booking', 'services', 'mechanics', 'history', 'pendingReschedule'
        ));
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
            // 'cancelled' is deliberately absent from the ladder. Reaching it
            // through this dropdown skipped the required-reason flow entirely,
            // which is the whole point of the cancel action. Use that instead.
            //
            // A booking that is already cancelled is the exception, and keeps
            // its own status as the only value it may post. It cannot move
            // anywhere — canMoveTo() refuses, because cancelled is not on
            // the ladder — but the form still has to submit. Refusing it
            // outright meant the edit screen for a cancelled booking could not
            // be saved at all: the select correctly showed "Cancelled", and
            // submitting it unchanged, even just to correct a note, failed with
            // "The selected status is invalid".
            'status'       => ['required', Rule::in(
                $booking->status === BookingStatus::Cancelled
                    ? [BookingStatus::Cancelled->value]
                    : BookingStatus::ladderValues()
            )],
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

        // A cancelled booking is the record of work that is not happening.
        // Same treatment as a completed one: the notes stay open so an admin
        // can still write down what was agreed, and everything else stands.
        // Falling through to the rest of this method would re-run the capacity
        // check and hand the booking back the slot that cancelling just freed.
        if ($booking->status === BookingStatus::Cancelled) {
            $booking->update(['notes' => $request->notes]);

            return redirect()->route('admin.bookings.cancelled')->with(
                'success',
                'Notes saved. '.$booking->reference_number.' is cancelled, so its status, '
                .'services and schedule stay as they are.'
            );
        }

        // Forward only. Cancelling is the one way out and has its own action,
        // because it requires a reason.
        //
        // Compared by value on both sides. $request->status is the string a
        // form posted and $oldStatus is now an enum, so `!==` between them was
        // true even when the status had not changed at all — which turned
        // "save a note on a cancelled booking" into "a booking cannot move
        // from Cancelled back to Cancelled".
        if ($request->status !== $oldStatus->value && ! $booking->canMoveTo($request->status)) {
            return back()->withInput()->withErrors([
                'status' => 'A booking cannot move from '.$oldStatus->label()
                            .' back to '.(BookingStatus::tryFrom($request->status)?->label() ?? $request->status)
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
        // slotMatches() normalises both sides. Comparing the raw strings
        // made every save on PostgreSQL look like a move, because the time
        // column reads back as 09:00:00 while the form posts 09:00.
        $slotMoved = ! $booking->slotMatches($request->booking_date, $request->booking_time);

        $rescheduled = $servicesMoved || $slotMoved;

        // The customer's appointment is the date and the time. Moving those is
        // proposed rather than applied — the booking keeps its slot until the
        // customer agrees. Everything else on this form still saves outright:
        // status, mechanic, notes and which services are on the booking are
        // the shop's record of the job, not the promise made to the customer.
        $duration = $booking->duration;

        if ($rescheduled) {
            $duration = $availability->durationForServices($serviceIds);

            // Where the booking will actually sit after this save. A proposed
            // move does not take effect here, so a services change is
            // re-checked against the slot the booking already holds.
            $targetDate = $slotMoved ? $booking->booking_date : $request->booking_date;
            $targetTime = $slotMoved ? $booking->booking_time : $request->booking_time;

            try {
                $availability->reschedule($booking, $targetDate, $targetTime, $duration);
            } catch (SlotUnavailableException $e) {
                return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
            }
        }

        if ($slotMoved) {
            // The limits that stop a booking being walked across the calendar
            // one reasonable-looking step at a time.
            if ($blocked = $booking->rescheduleBlockedReason()) {
                return back()->withInput()->withErrors(['booking_date' => $blocked]);
            }

            $reason = trim((string) $request->input('reschedule_reason'));

            if ($reason === '') {
                return back()->withInput()->withErrors([
                    'reschedule_reason' => 'Give the customer a reason for moving their booking.',
                ]);
            }

            // Refuse an impossible time while the admin is still looking at
            // the form. Checked again when the request is applied, because the
            // proposed slot is deliberately not held in the meantime.
            try {
                $availability->reschedule(
                    $booking, $request->booking_date, $request->booking_time, $duration
                );
            } catch (SlotUnavailableException $e) {
                return back()->withInput()->withErrors(['booking_time' => $e->getMessage()]);
            }

            $proposal = $this->proposeReschedule(
                $booking,
                $request->booking_date,
                $request->booking_time,
                $reason,
                $request->user()->id
            );

            // The update below reads the request, so the proposed date and
            // time must not reach it: the booking keeps the slot it has.
            $request->merge([
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
            ]);

            session()->flash('reschedule_proposed',
                'The customer has been asked to approve moving '.$booking->reference_number
                .' to '.$proposal->proposedLabel().'. Their booking is unchanged until they agree.'
            );
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
        //
        // The status audit row, the loyalty award, the service-history stamp,
        // the bell entries, the confirmation email and both texts all moved
        // into BookingProgress, which the queue board's Advance control calls
        // too. They were inline here while this form was the only way a status
        // could move; a second way meant either sharing them or copying them,
        // and a copy is how a car advanced on the board would have earned no
        // points and never told its owner it was ready.
        $newStatus = BookingStatus::from($request->status);

        app(BookingProgress::class)->statusChanged($booking, $oldStatus, $newStatus);

        if ($servicesMoved) {
            AuditLog::record(
                'booking.services_changed', $booking,
                ['service_ids' => $sortedOld], ['service_ids' => $sortedNew],
                label: $booking->reference_number,
            );
        }

        if (! $booking->slotMatches($oldDate, $oldTime)) {
            AuditLog::record(
                'booking.rescheduled', $booking,
                ['booking_date' => $oldDate, 'booking_time' => $oldTime],
                ['booking_date' => $booking->booking_date, 'booking_time' => $booking->booking_time],
                label: $booking->reference_number,
            );
        }

        // Tell the customer the slot moved. Only on a real move — this fired
        // on every save on PostgreSQL, announcing "moved from 09:00:00 to
        // 09:00:00". The status notices live in BookingProgress now.
        if ($booking->user && ! $booking->slotMatches($oldDate, $oldTime)) {
            $booking->user->notify(
                BookingNotification::rescheduled($booking->fresh(), $oldDate, $oldTime)
            );
        }

        // The plate is deliberately not editable here. This form used to write
        // $request->plate straight onto the Vehicle with no validation at all,
        // while the customer's own vehicle form enforces the LTO format and
        // uniqueness — so "nonsense!!!" was accepted. It also wrote to the
        // shared Vehicle row, changing the plate on every other booking for
        // that car. A plate belongs to the vehicle, not to one booking.

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * The queue board: what is physically in the shop, right now.
     *
     * This was a timeline of everything booked today, in booking_time order,
     * which answered a question the shop does not have — they have the
     * appointments in the bookings list. What they cannot see anywhere else is
     * which cars are actually here and how far along each one is.
     *
     * So only the four statuses that mean a vehicle is on the premises appear.
     * Pending Review and Booking Confirmed are deliberately absent: they are
     * expectations, not cars. They arrive here the moment somebody marks them
     * arrived, which is what keeps the board a picture of the shop rather than
     * of the calendar — and the counts below say how many are still to come,
     * so nobody is left wondering where they went.
     */
    public function schedule()
    {
        $onBoard = [
            BookingStatus::Arrived,
            BookingStatus::VehicleInspection,
            BookingStatus::ServiceInProgress,
            BookingStatus::ServiceCompleted,
        ];

        $bookings = Booking::with(['user', 'service', 'vehicle', 'mechanic', 'services'])
            ->whereDate('booking_date', today())
            ->whereIn('status', array_map(fn (BookingStatus $s) => $s->value, $onBoard))
            ->get()
            // Sorted in PHP, not SQL. The queue order is arrived_at ascending
            // with the unstamped rows last, and "nulls last" is spelled
            // differently on every driver — this is one day of one shop's
            // bookings, so the cost of sorting a handful of rows in memory
            // buys a sentence that reads the same on SQLite and PostgreSQL.
            //
            // A null arrived_at is possible: an admin can take a booking
            // straight to Service Completed on the edit form without anyone
            // pressing Arrived. Those belong on the board — their status says
            // the work happened — but they cannot hold a place in a queue
            // ordered by arrival, so they go to the end and show no number.
            ->sortBy([
                fn (Booking $a, Booking $b) => ($a->arrived_at === null) <=> ($b->arrived_at === null),
                fn (Booking $a, Booking $b) => ($a->arrived_at?->timestamp ?? 0) <=> ($b->arrived_at?->timestamp ?? 0),
                fn (Booking $a, Booking $b) => $a->id <=> $b->id,
            ])
            ->values();

        // Queue numbers are per DAY, not per column, and are assigned in
        // arrival order — so a car keeps its number as it moves across the
        // board, which is the whole point of having one. Staff call out "car
        // four", and car four must not become car one by being the first to
        // reach Vehicle Inspection.
        //
        // Derived here rather than stored. A column would have to be kept in
        // step with arrived_at by every path that writes it, and the two would
        // eventually disagree about a real booking; this cannot, because
        // arrived_at is the only input.
        $queueNumbers = [];
        $next         = 1;

        foreach ($bookings as $booking) {
            $queueNumbers[$booking->id] = $booking->arrived_at === null ? null : $next++;
        }

        // One column per status, in ladder order, each already in queue order
        // because $bookings was sorted before it was grouped.
        $columns = collect($onBoard)->mapWithKeys(fn (BookingStatus $s) => [
            $s->value => $bookings->filter(fn (Booking $b) => $b->status === $s)->values(),
        ]);

        // Still to come: booked for today and not here yet. Counted, not
        // listed — the board is for what is in the shop — but counted
        // visibly, because a car that vanished off this screen when the
        // timeline became a queue is the first thing staff would ask about.
        $expected = Booking::whereDate('booking_date', today())
            ->whereIn('status', [
                BookingStatus::PendingReview->value,
                BookingStatus::BookingConfirmed->value,
            ])
            ->get();

        return view('admin.bookings.schedule', [
            'columns'       => $columns,
            'queueNumbers'  => $queueNumbers,
            'onBoard'       => $onBoard,
            'inShop'        => $bookings->count(),
            // Reported separately rather than summed. A booking the shop has
            // not agreed to yet is not "confirmed for today", and calling it
            // that on a screen staff act on would be the sort of small lie
            // that gets someone turned away at the counter.
            'awaitingArrival' => $expected
                ->filter(fn (Booking $b) => $b->status === BookingStatus::BookingConfirmed)->count(),
            'awaitingReview'  => $expected
                ->filter(fn (Booking $b) => $b->status === BookingStatus::PendingReview)->count(),
        ]);
    }

    /**
     * Move a booking one step up the ladder.
     *
     * The queue board's Advance control. One step, never a jump: the enum
     * decides what next is, so this action does not know or care what the
     * ladder looks like, and a board that moved a booking two places because
     * the ladder changed shape would be worse than one that did nothing.
     *
     * Every consequence goes through BookingProgress, the same as the edit
     * form — which is why it exists. Advancing the last step is a completion,
     * and a completion earns points, stamps the vehicle's service history and
     * texts the customer that the car is ready.
     */
    public function advance($id)
    {
        $booking = Booking::findOrFail($id);
        $from    = $booking->status;
        $next    = $from->next();

        // Service Completed has no next step, and neither does Cancelled.
        // Refused as 422 with something the board can display, rather than as
        // a 404 with an HTML body the caller cannot parse — the mistake the
        // Arrived action made for months.
        if ($next === null || ! $booking->canMoveTo($next)) {
            return response()->json([
                'success' => false,
                'message' => $booking->reference_number.' is '.$from->label()
                             .', so there is nothing to advance it to.',
            ], 422);
        }

        // Re-check inside the write, so two people pressing Advance on the
        // same card — or one person pressing it twice — cannot both pass the
        // guard above and move the booking two steps. 0 rows means somebody
        // else got there first.
        $moved = Booking::where('id', $booking->id)
            ->where('status', $from->value)
            ->update(['status' => $next->value]);

        if ($moved === 0) {
            return response()->json([
                'success' => false,
                'message' => 'This booking was just updated by someone else. Refresh to see its current status.',
            ], 409);
        }

        app(BookingProgress::class)->statusChanged($booking->fresh(), $from, $next);

        return response()->json([
            'success'      => true,
            'status'       => $next->value,
            'status_label' => $next->label(),
            'badge_class'  => $next->badgeClass(),
            // Null once there is nowhere left to go, which is how the board
            // knows to stop drawing the button.
            'next'         => $next->next()?->value,
            'message'      => $booking->reference_number.' is now '.$next->label().'.',
        ]);
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

        // Only a cancelled booking has anything to rebook.
        //
        // The route is a plain GET with the id in the address bar, and the only
        // link to it sits on the cancelled list — so the guard was the list
        // itself, which a typed URL, a bookmark or a stale tab walks straight
        // past. Rebooking a live booking prefilled the form from it and created
        // a second one for the same customer and car; on a different date
        // nothing downstream objects, and the customer ends up with two. A
        // completed booking is a record of work already done and should not be
        // a starting point for anything.
        if ($original->status !== BookingStatus::Cancelled) {
            $label = $original->status->label();

            return redirect()->route('admin.bookings.cancelled')->with('error',
                $original->reference_number.' is '.$label.', not cancelled, so there is nothing to rebook. '
                .($original->isLocked()
                    ? 'A finished job is a record of work already done — start a new booking instead.'
                    : 'Open the booking to change it, or cancel it first if the work is not going ahead.'));
        }

        $services  = Service::orderBy('name')->get();
        $mechanics = Mechanic::active()->get();

        [$prefillDate, $prefillTime] = $this->rebookSlot($original);

        return view('admin.bookings.create', [
            'services'     => $services,
            'mechanics'    => $mechanics,
            'prefill'      => $original,
            'prefillDate'  => $prefillDate,
            'prefillTime'  => $prefillTime,
        ]);
    }

    /**
     * A stand-in address for a walk-in who did not give one.
     *
     * The account exists so the booking has somewhere to live; nobody reads
     * the address, and BookingNotification refuses to mail anything ending in
     * the placeholder domain.
     *
     * It has to be unique, because users.email is. This was built from the
     * name alone, so a second customer called Maria Santos — not a remote
     * possibility in Quezon City — collided with the first and the New Booking
     * form returned a 500 with nothing created. The phone number is what the
     * account is actually keyed on, so it is what disambiguates.
     *
     * withTrashed for the same reason the plate lookup needs it: a soft-deleted
     * user still occupies the unique index, so an address that looks free is
     * not necessarily free.
     */
    private function placeholderEmailFor(?string $name, ?string $phone): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $name)) ?: 'walkin';
        $domain = BookingNotification::PLACEHOLDER_DOMAIN;

        if (! User::withTrashed()->where('email', $base.$domain)->exists()) {
            return $base.$domain;
        }

        $digits = preg_replace('/\D/', '', (string) $phone);

        return $base.'.'.($digits ?: uniqid()).$domain;
    }

    /**
     * The slot a rebook should open on: [date, time].
     *
     * The old booking's date is nearly always in the past, so the date resets
     * — but to the next day the shop is actually open rather than to tomorrow
     * flat. Tomorrow can be a closed day, and the form then rendered an empty
     * calendar with no explanation.
     *
     * The time used to be carried over from the original with nothing checking
     * it against the new date. Rebooking a cancelled afternoon booking onto a
     * weekend, when the shop shuts at noon, prefilled an hour the shop is shut:
     * the calendar drew nothing, the hidden time field still held the old
     * value, and submitting was refused by assertWithinHours() — a form that
     * could not be submitted and no word of why.
     *
     * So the time is carried over only when it is still a bookable slot on the
     * resolved date, and left blank otherwise. Deliberately not rounded to the
     * nearest open slot: a time the admin did not choose, on a confirmation
     * screen, is worse than an empty field they have to fill in.
     *
     * @return array{0: string, 1: string}
     */
    private function rebookSlot(Booking $original): array
    {
        $availability = app(BookingAvailability::class);

        $duration = (int) ($original->duration
            ?: $availability->durationForServices($original->services->pluck('id')->all()));

        if ($duration <= 0) {
            $duration = $availability->gridMinutes();
        }

        // Tomorrow, then forward to the next day that takes bookings at all.
        // Bounded: a shop configured closed every day should give the admin a
        // date to look at and let the calendar say the rest, not spin.
        $date = now()->addDay()->startOfDay();

        for ($i = 0; $i < 14 && ! $availability->isOpenOn($date); $i++) {
            $date->addDay();
        }

        $dateString = $date->toDateString();
        $time       = substr((string) $original->booking_time, 0, 5);

        // A day already at the shop-wide cap has no room whatever the grid
        // says — dayAvailability() reads capacity and the clock, not Settings'
        // daily limit — so carrying a time onto it would prefill a slot
        // reserve() is bound to refuse.
        $cap    = $availability->dailyCap();
        $atCap  = $cap > 0 && $availability->bookingsOnDate($dateString) >= $cap;

        $stillBookable = ! $atCap && collect($availability->dayAvailability($dateString, $duration))
            ->contains(fn (array $slot) => $slot['start'] === $time && $slot['bookable']);

        return [$dateString, $stillBookable ? $time : ''];
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

        // Every consequence of a cancellation — the audit row, the
        // loyalty reversal and refund, and telling the customer — lives in
        // BookingCancellation, so the customer dashboard, the no-show sweep
        // and this screen cannot drift apart. They did: three of the four
        // ways of cancelling a booking skipped the ledger entirely.
        $cancelled = app(BookingCancellation::class)
            ->cancel($booking, $validated['reason'], $request->user()->id);

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is '.$booking->status->label()
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


    /**
     * Mark a booking "Arrived" and start the service — in_progress protects it
     * from the no-show auto-cancel job.
     *
     * This previously gated status with whereIn(...)->findOrFail(), which did
     * block invalid transitions but surfaced them as a 404 with an HTML body.
     * The caller's r.json() then threw on that HTML and the rejection was only
     * console.error'd, so nothing appeared at all and the button looked dead.
     * Refused transitions now return 422 with a message the UI can display.
     *
     * Arrival is a status as well as a timestamp now.
     *
     * It used to be only a timestamp: this action stamped arrived_at and set
     * the status straight to in_progress, so a car checked in at the counter
     * was reported as being worked on before anybody had looked at it. The
     * ladder has Arrived and Vehicle Inspection between those two, and this
     * action is the step onto the first of them.
     *
     * The column stays, and both are written here. arrived_at is what the
     * queue board orders by — it is the one piece of information that says
     * who was here first, which a status cannot — so there is still exactly
     * one way to mark a car as arrived.
     */
    public function arrive($id)
    {
        $booking = Booking::findOrFail($id);

        if (! $booking->canStart()) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is '.$booking->status->label()
                             .'. Only a booking that is '
                             .collect(BookingStatus::startableValues())
                                ->map(fn (string $v) => BookingStatus::from($v)->label())
                                ->join(' or ')
                             .' can be marked arrived.',
            ], 422);
        }

        // Re-check the status inside the write so two quick clicks (or two tabs
        // on the same booking) can't both pass the guard above and double-stamp
        // arrived_at. 0 rows means someone else got there first.
        $started = Booking::where('id', $booking->id)
            ->whereIn('status', BookingStatus::startableValues())
            ->update([
                'status'     => BookingStatus::Arrived->value,
                'arrived_at' => now(),
            ]);

        if ($started === 0) {
            return response()->json([
                'success' => false,
                'message' => 'This booking was just updated by someone else. Refresh to see its current status.',
            ], 409);
        }

        // Arriving has no side effects of its own — no points, no mail, no
        // text — so BookingProgress is still called, for the audit row and so
        // that a future consequence of arriving has one place to be added.
        app(BookingProgress::class)->statusChanged(
            $booking->fresh(), $booking->status, BookingStatus::Arrived
        );

        return response()->json([
            'success'      => true,
            'status'       => BookingStatus::Arrived->value,
            'status_label' => BookingStatus::Arrived->label(),
            'badge_class'  => BookingStatus::Arrived->badgeClass(),
            'message'      => $booking->reference_number.' is marked arrived.',
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
            ['Reference', 'Customer', 'Vehicle Type', 'Transmission', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
            $this->bookingRows(Booking::with(['user', 'service', 'vehicle', 'services'])->latest()->cursor())
        );
    }

    /** CSV of cancelled bookings, matching the Cancelled screen. */
    public function exportCancelled()
    {
        return CsvExport::stream(
            CsvExport::filename('cancelled-bookings'),
            ['Reference', 'Customer', 'Vehicle Type', 'Transmission', 'Service', 'Date', 'Time', 'Status', 'Booked On'],
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
                // The kind of vehicle and its gearbox, from the booking's own
                // snapshot — so a file exported last month still says what
                // was booked then.
                //
                // "Vehicle Type", not "Vehicle". A Vehicle column holding the
                // make, model and plate was deliberately taken out of every
                // export, and the header has to stay distinguishable from
                // that one: this is 'Car' or 'Motorcycle' and identifies
                // nobody, which is exactly why it is allowed back in.
                $b->vehicle_type_label ?? '',
                $b->transmission_label ?? '',
                $b->service_list,
                $b->booking_date,
                $b->booking_time,
                // The enum's label. This was the stored value with its
                // underscores swapped out and the first letter raised, which
                // spelled "In progress" where every screen said "In Progress"
                // — and would now spell "Service in progress".
                $b->status->label(),
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
    }

    /**
     * Write the trail row for a booking the shop just created.
     *
     * store() wrote none at all, while cancel, delete, reschedule and mechanic
     * assignment all do — so a booking could appear with nothing saying who
     * made it. And a rebook left no trace of being one: the banner named the
     * cancelled booking it came from, but nothing carried that into the
     * submit, so afterwards there was no way to tell that this booking stood
     * in for that one.
     *
     * The reference is checked, not trusted. It arrives in a hidden field, so
     * it can be anything: a reference that names nothing, or names a booking
     * that is not cancelled, is logged as an ordinary creation. A rebook must
     * never fail over its own audit row — AuditLog::record() already swallows
     * its own errors for the same reason.
     *
     * The new booking is the subject, so the trail already rendered on its
     * edit screen shows this with no view changes.
     */
    private function recordCreation(Booking $booking, ?string $rebookedFrom): void
    {
        $original = $rebookedFrom
            ? Booking::where('reference_number', $rebookedFrom)->where('status', 'cancelled')->first()
            : null;

        AuditLog::record(
            $original ? 'booking.rebooked' : 'booking.created',
            $booking,
            $original ? ['rebooked_from' => $original->reference_number] : [],
            [
                'status'       => $booking->status,
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
            ],
            $original
                ? 'Replaces cancelled booking '.$original->reference_number
                : null,
            label: $booking->reference_number,
        );
    }

    /**
     * Tell the customer the shop has booked something for them.
     *
     * store() told the admins and nobody else. requested is not in
     * BookingNotification::MAILED and goes only to admins, so a customer whose
     * booking the shop created — a walk-in, or a rebook from the cancelled
     * list — received nothing at all: no email, not even a bell entry. They
     * found out by signing in and noticing, or by not turning up.
     *
     * The guest form already emails BookingReceived, and a customer booking
     * from their own dashboard clicked the button and saw the confirmation on
     * screen. Admin is the one case where the customer did not choose the
     * appointment, which is exactly why this one needs telling.
     *
     * The bell entry is written first and separately: the notification history
     * should show the booking even when the inbox never does.
     */
    private function notifyCustomerOfBooking(Booking $booking): void
    {
        $customer = $booking->user;

        if (! $customer) {
            return;
        }

        try {
            $customer->notify(BookingNotification::received(
                $booking->fresh()->load(['user', 'services'])
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'Booking received notice failed for '.$booking->reference_number.': '.$e->getMessage()
            );
        }

        // A walk-in with no email gets a synthesised @apxautomai.local address
        // so the account can hold bookings. Nobody reads it, and mailing it
        // fails slowly against a real SMTP server. Same test the notification
        // channel applies, asked of the same place rather than copied.
        if (! BookingNotification::hasRealAddress($customer)) {
            return;
        }

        // Inline, because nothing on Render runs a queue worker. The booking
        // is already written and committed; a Brevo outage must leave it that
        // way rather than turning a saved booking into a 500 on the form.
        try {
            Mail::to($customer->email)->send(new BookingReceived(
                $booking->fresh()->load(['user', 'service', 'vehicle', 'services'])
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'Booking received email failed for '.$booking->reference_number.': '.$e->getMessage()
            );
        }
    }

    // ── Proposed schedule changes ─────────────────────────────────────────

    /**
     * Record that the shop would like to move this booking, and tell the
     * customer. The booking itself is not touched.
     *
     * Audited, so "this was moved three times" is visible afterwards rather
     * than being a thing only the customer remembers.
     */
    private function proposeReschedule(
        Booking $booking,
        string $date,
        string $time,
        string $reason,
        ?int $byUserId
    ): RescheduleRequest {
        $proposal = RescheduleRequest::create([
            'booking_id'    => $booking->id,
            'proposed_date' => $date,
            'proposed_time' => $time,
            'original_date' => $booking->booking_date,
            'original_time' => $booking->booking_time,
            'reason'        => $reason,
            'status'        => RescheduleRequest::PENDING,
            'proposed_by'   => $byUserId,
        ]);

        AuditLog::record(
            'booking.reschedule_proposed', $booking,
            ['booking_date' => $booking->booking_date, 'booking_time' => $booking->booking_time],
            ['booking_date' => $date, 'booking_time' => $time, 'reason' => $reason],
            label: $booking->reference_number,
        );

        if ($booking->user) {
            // The booking is already saved either way; failing to reach the
            // customer must not undo it, the same as a cancellation.
            try {
                $booking->user->notify(
                    BookingNotification::rescheduleProposed($booking->fresh(), $proposal)
                );
            } catch (\Throwable $e) {
                \Log::error('Reschedule proposal notice failed for '
                    .$booking->reference_number.': '.$e->getMessage());
            }
        }

        return $proposal;
    }

    /**
     * The customer agreed, so move the booking.
     *
     * The proposed slot was never held — holding it would have meant one
     * booking occupying two slots while the customer thought about it — so it
     * is re-checked here and the move fails cleanly if the time has gone,
     * leaving the original booking exactly as it was.
     */
    public function applyReschedule(Request $request, $id)
    {
        $booking  = Booking::findOrFail($id);
        $proposal = $booking->pendingReschedule();

        if (! $proposal) {
            return back()->withErrors(['reschedule' => 'There is no schedule change waiting on this booking.']);
        }

        $availability = app(BookingAvailability::class);
        $date = $proposal->proposed_date->toDateString();
        $time = $proposal->proposed_time;

        try {
            $availability->reschedule($booking, $date, $time, $booking->duration);
        } catch (SlotUnavailableException $e) {
            return back()->withErrors([
                'reschedule' => 'That time is no longer free, so the booking has not moved: '
                    .$e->getMessage().' Withdraw this change and propose another time.',
            ]);
        }

        $oldDate = $booking->booking_date;
        $oldTime = $booking->booking_time;

        \DB::transaction(function () use ($booking, $proposal, $date, $time, $request) {
            $booking->update(['booking_date' => $date, 'booking_time' => $time]);

            $proposal->update([
                'status'      => RescheduleRequest::APPLIED,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        });

        AuditLog::record(
            'booking.reschedule_applied', $booking,
            ['booking_date' => $oldDate, 'booking_time' => $oldTime],
            ['booking_date' => $date, 'booking_time' => $time],
            label: $booking->reference_number,
        );

        if ($booking->user) {
            try {
                $booking->user->notify(
                    BookingNotification::rescheduled($booking->fresh(), $oldDate, $oldTime)
                );
            } catch (\Throwable $e) {
                \Log::error('Reschedule notice failed for '.$booking->reference_number.': '.$e->getMessage());
            }
        }

        return back()->with('success',
            $booking->reference_number.' has moved to '.$proposal->proposedLabel().'.');
    }

    /** The shop takes the request back. The booking never moved, so nothing undoes. */
    public function withdrawReschedule(Request $request, $id)
    {
        $booking  = Booking::findOrFail($id);
        $proposal = $booking->pendingReschedule();

        if (! $proposal) {
            return back()->withErrors(['reschedule' => 'There is no schedule change waiting on this booking.']);
        }

        $proposal->update([
            'status'      => RescheduleRequest::WITHDRAWN,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        AuditLog::record(
            'booking.reschedule_withdrawn', $booking,
            ['proposed_date' => $proposal->proposed_date->toDateString(), 'proposed_time' => $proposal->proposed_time],
            [],
            label: $booking->reference_number,
        );

        return back()->with('success',
            'The proposed change to '.$booking->reference_number.' has been withdrawn. '
            .'The booking stands as it was.');
    }
}
