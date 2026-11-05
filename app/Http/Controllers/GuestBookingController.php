<?php

namespace App\Http\Controllers;

use App\Support\BookingPolicy;
use App\Support\PlateNumber;
use App\Support\EmailRule;

use App\Models\Bundle;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Mail\BookingReceived;
use App\Mail\GuestAccountCreated;
use App\Services\BookingAvailability;
use App\Services\BookingPricing;
use App\Exceptions\SlotUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'guest_email' => strtolower(trim((string) $request->input('guest_email'))),
        ]);

        // Normalised before validating, not after: the rules below reject a
        // leading, trailing or doubled space, so cleaning has to happen first
        // or a customer typing "abc 1234 " is refused rather than tidied.
        $request->merge(['vehicle_plate' => PlateNumber::normalise($request->input('vehicle_plate'))]);

        $request->validate([
            'guest_name'     => 'required|string|max:255',
            'guest_email'    => ['required', EmailRule::strict(), 'max:255'],
            'booking_date'   => 'required|date|after_or_equal:today',
            'booking_time'   => 'required',
            // A bundle supplies its own services, so one or the other.
            'bundle_id'      => 'nullable|integer|exists:bundles,id',
            'service_ids'    => [$request->filled('bundle_id') ? 'nullable' : 'required', 'array'],
            'service_ids.*'  => 'integer|exists:services,id',
            'vehicle_make'   => 'required|string|max:100',
            'vehicle_model'  => 'required|string|max:100',
            'vehicle_year'   => 'required|integer|min:2000',
            'vehicle_plate'  => PlateNumber::rules(),
            'vehicle_type'   => 'nullable|in:car,motorcycle',
        ], [
            ...PlateNumber::messages('vehicle_plate'),
        ]);

        // ── Block real accounts from booking as a guest ───────────────────
        // This gate used to test email_verified_at, which nothing in this app
        // ever sets, so it never fired: a guest booking with a known email was
        // silently filed into that person's account. is_guest marks an account
        // the shop created on someone's behalf, which they have never set a
        // password for; booking again as a guest on one of those is fine.
        $existingUser = User::where('email', $request->guest_email)->first();

        if ($existingUser && ! $existingUser->is_guest) {
            return response()->json([
                'success' => false,
                'message' => 'This email already has an account. Please log in to book a service.',
            ], 422);
        }

        // ── Everything that can refuse this booking, before anything is
        //    written ──────────────────────────────────────────────────────
        //
        // The account used to be created here, at the top, with the checks
        // below running afterwards. Every one of them returns 422 — a plate
        // held by someone else, a withdrawn bundle, a service that does not
        // suit the vehicle, complimentary work on its own, a slot that has
        // just gone — and the users row stayed behind with no booking on it.
        // The customer was told their booking failed while quietly holding an
        // account they had never heard of, which /register then refuses
        // because the address is taken, and which no setup link was ever sent
        // for, because that only happens once a booking exists. Two such rows
        // are in the live data.
        //
        // So nothing is written until the answer is yes. $existingUser stands
        // in for the account through these checks: it is null for a first-time
        // guest, which is exactly the case that used to leave the stray row.

        // A bundle decides its own contents; services posted alongside one are
        // ignored, or a bundle could be loaded with extra work at its price.
        $bundle = $request->filled('bundle_id')
            ? Bundle::with('services')->bookable()->find($request->bundle_id)
            : null;

        if ($request->filled('bundle_id') && ! $bundle) {
            return response()->json([
                'success' => false,
                'message' => 'That bundle is no longer available. Please choose again.',
            ], 422);
        }

        if ($bundle && ! $bundle->suitsVehicleType($request->vehicle_type)) {
            return response()->json([
                'success' => false,
                'message' => 'That bundle is for '.$bundle->vehicle_type.'s only.',
            ], 422);
        }

        $serviceIds = $bundle
            ? $bundle->services->pluck('id')->all()
            : (array) $request->service_ids;

        $services = Service::whereIn('id', $serviceIds)->get();

        if ($services->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services could not be found. Please try again.',
            ], 422);
        }

        // Not every service suits every vehicle — a motorcycle has no aircon.
        // The modal hides these, but the browser chooses what to post, so the
        // answer has to be given again here. A bundle is exempt: it carries its
        // own vehicle_type, already checked above, and its contents are the
        // shop's choice rather than the customer's.
        if (! $bundle) {
            $wrongVehicle = BookingPolicy::servicesNotFor($services, $request->vehicle_type);

            if ($wrongVehicle->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => BookingPolicy::wrongVehicleMessage($wrongVehicle, $request->vehicle_type),
                ], 422);
            }
        }

        // Complimentary work accompanies paid work; it is not a booking of its
        // own. Checked here, after the services are resolved, so a bundle and a
        // loose list of ids both go through the same rule.
        if (BookingPolicy::isFreeOnly($services, $bundle)) {
            return response()->json([
                'success' => false,
                'message' => BookingPolicy::FREE_ONLY_MESSAGE,
            ], 422);
        }

        $plateKey = PlateNumber::normalise($request->vehicle_plate);

        // Through the shared lookup, which sees soft-deleted rows. This was a
        // plain Vehicle::where(...), which the soft-delete scope hides trashed
        // rows from, so a plate on a deleted vehicle looked free: the guard
        // passed and the insert below hit the unique index, returning a 500 to
        // a guest mid-booking.
        $held = Vehicle::holdingPlate($plateKey);

        // plate_number is globally unique, so a plate held by someone else
        // cannot be reused — say so rather than failing on the constraint.
        // Scoped to this address on purpose: looking up by plate alone used to
        // bind the booking to whichever account already owned that plate,
        // which both leaked that customer's vehicle details and let anyone who
        // could read a plate off a car attach themselves to it. With no
        // account yet, any held plate belongs to somebody else.
        if ($held && (! $existingUser || $held->user_id !== $existingUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'That plate number is already registered to another account. Please log in to book with it, or contact us if this is your vehicle.',
            ], 422);
        }

        $firstService = $services->first();

        // ── The answer is yes. Write it, all or nothing ────────────────────
        //
        // One transaction over the account, the vehicle and the booking.
        // reserve() is the last thing that can still refuse — the slot may
        // have gone between drawing the picker and pressing confirm — and it
        // throws rather than returning, so without the transaction its failure
        // would leave behind exactly the stray account and vehicle this
        // ordering exists to prevent.
        $availability = app(BookingAvailability::class);
        $pricing      = app(BookingPricing::class);

        try {
            [$user, $booking, $isNewGuest] = DB::transaction(function () use (
                $request, $held, $plateKey, $services, $serviceIds, $bundle,
                $firstService, $availability, $pricing
            ) {
                // Matching on email alone is safe: the guard above has already
                // turned away any address belonging to a registered account,
                // so anything found is a guest record this flow created.
                $user = User::firstOrCreate(
                    ['email' => $request->guest_email],
                    [
                        'name'      => $request->guest_name,
                        'phone'     => $request->guest_phone ?? null,
                        'role'      => 'customer',
                        'password'  => bcrypt(Str::random(16)),
                        'is_active' => true,
                        'is_guest'  => true,
                    ]
                );

                $isNewGuest = $user->wasRecentlyCreated;

                if (! $isNewGuest) {
                    $user->update([
                        'name'  => $request->guest_name,
                        'phone' => $request->guest_phone ?? $user->phone,
                    ]);
                }

                $vehicle = $held;

                if ($vehicle && $vehicle->trashed()) {
                    // Their own vehicle from an earlier visit, since deleted.
                    // Restored rather than duplicated, which the unique index
                    // forbids anyway.
                    $vehicle->restore();
                }

                if (! $vehicle) {
                    $vehicle = Vehicle::create([
                        'user_id'      => $user->id,
                        'plate_number' => $plateKey,
                        'make'         => $request->vehicle_make,
                        'model'        => $request->vehicle_model,
                        'vehicle_type' => in_array($request->vehicle_type, ['car', 'motorcycle']) ? $request->vehicle_type : 'car',
                        'year'         => $request->vehicle_year,
                        'color'        => null,
                    ]);
                }

                // One summed-duration block across all selected services,
                // gated by the same capacity/hours rules as every other
                // booking flow.
                $booking = $availability->reserve([
                    'user_id'      => $user->id,
                    'vehicle_id'   => $vehicle->id,
                    'service_id'   => $firstService->id,
                    'service_ids'  => $serviceIds,
                    'mechanic_id'  => null,
                    'booking_date' => $request->booking_date,
                    'booking_time' => $request->booking_time,
                    'status'       => 'pending',
                    // Extra services used to be appended to the note as
                    // "[Additional services: ...]" because only one could be
                    // stored. They are real pivot rows now, so the note is the
                    // customer's own text again.
                    'notes'        => $request->notes ?: null,
                ]);

                // Price it server side. A guest gets no account discount —
                // that is the whole point of the offer — but the same code
                // decides it, so there is one answer to what a booking costs.
                $pricing->apply(
                    $booking,
                    $pricing->quote($services, $bundle, $user),
                    $services
                );

                return [$user, $booking, $isNewGuest];
            });
        } catch (SlotUnavailableException $e) {
            // Rolled back: no account, no vehicle, no booking.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── Will a "set up your account" link go out with this booking? ───
        //
        // A new guest always gets one: their account exists and they have never
        // seen its password. A returning guest who has still not claimed theirs
        // gets another only if they asked for an account this time — the first
        // link lasts 60 minutes, so by a second visit it is almost certainly
        // dead, and sending nothing left them with a half-made account, no way
        // into it, and a success screen that mentioned neither.
        //
        // recentlyCreatedToken is the password broker's own throttle, so
        // resubmitting cannot be used to mail someone repeatedly.
        //
        // Computed here, once, because the success screen has to say whether a
        // link was sent and must not guess: create_account is a checkbox, not a
        // record of anything having happened.
        $sendAccountLink = $isNewGuest
            || ($user->is_guest
                && $request->boolean('create_account')
                && ! Password::getRepository()->recentlyCreatedToken($user));

        $this->notifyAdminsOfBooking($booking);

        // ── Send emails, after the customer has their answer ───────────────
        //
        // These went out inside the request. One SMTP round trip measures
        // about 3.3 seconds, and a new guest gets two, so the customer sat
        // on a "Submitting…" button for the better part of seven seconds
        // after their booking had already been saved. It looked hung, and a
        // hung Confirm button is how you lose a booking that already exists.
        //
        // afterResponse runs this once the response has been flushed to the
        // browser, in the same process — no queue worker, and nothing for
        // Render to pay for. The booking is what matters; the email is a
        // courtesy that can take its time.
        $booking->load(['user', 'service', 'vehicle']);
        $guestEmail = $request->guest_email;

        dispatch(function () use ($booking, $guestEmail, $sendAccountLink, $user) {
            try {
                Mail::to($guestEmail)->send(new BookingReceived($booking));

                // Send the setup link, if one is owed. Decided before the
                // response went out, so what the customer was told on screen
                // and what lands in their inbox cannot disagree.
                if ($sendAccountLink) {
                    $token    = Password::createToken($user);
                    $resetUrl = url(route('password.reset', [
                        'token' => $token,
                        'email' => $user->email,
                    ], false));

                    Mail::to($guestEmail)->send(new GuestAccountCreated($user, $resetUrl));
                }
            } catch (\Throwable $e) {
                \Log::error('Guest booking email failed: '.$e->getMessage());
            }
        })->afterResponse();

        // No 'reference' here, deliberately. The booking is 'pending' and the
        // reference is withheld until an admin confirms it — the success
        // screen used to print it under "Bring this number when you arrive",
        // which told the customer they had an appointment nobody had agreed
        // to yet. BookingConfirmed is the only place it is disclosed.
        // booking_id is still returned: it is the dashboard's merge key and
        // means nothing at the counter.
        return response()->json([
            'success'        => true,
            'booking_id'     => $booking->id,
            'create_account' => $request->boolean('create_account'),
            'register_url'   => route('register'),
            // What the success screen needs: not what was asked for, but what
            // was done. The modal used to ignore the response beyond the
            // reference number and show one fixed line, so a customer who
            // asked for an account was never told a link had been emailed.
            'account_setup_email' => $sendAccountLink,
        ]);
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
