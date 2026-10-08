<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single source of truth for booking capacity, operating hours, and the
 * no-show grace period. Every booking creator (customer, admin, guest) and
 * every reschedule must go through here so the "3 mechanics" rule can never
 * be bypassed by one of the flows.
 */
class BookingAvailability
{
    /** Shop-wide concurrent booking limit (3 mechanics). */
    public const MECHANIC_CAPACITY = 3;

    /** Start-time grid step, in minutes. */
    public const GRID_MINUTES = 30;

    /** Minutes after a booking's start before a no-show auto-cancels it. */
    public const GRACE_MINUTES = 15;

    /** Statuses that occupy a mechanic slot. */
    private const ACTIVE_STATUSES = ['pending', 'confirmed', 'in_progress'];

    /**
     * Operating hours by ISO weekday (1 = Monday ... 7 = Sunday), as [open, close].
     */
    private const HOURS = [
        1 => ['09:00', '21:00'],
        2 => ['09:00', '21:00'],
        3 => ['09:00', '21:00'],
        4 => ['09:00', '21:00'],
        5 => ['09:00', '21:00'],
        6 => ['09:00', '12:00'],
        7 => ['09:00', '12:00'],
    ];

    /**
     * The [open, close] Carbon instants for the given date, per the
     * operating-hours map above.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function hoursFor(Carbon $date): array
    {
        // Admin-configurable via Settings -> Booking. The HOURS map above is the
        // fallback for any value that has never been saved.
        $isWeekend = $date->dayOfWeekIso >= 6;
        [$fallbackOpen, $fallbackClose] = self::HOURS[$date->dayOfWeekIso];

        $open  = Setting::get($isWeekend ? 'weekend_open_time'  : 'open_time')  ?: $fallbackOpen;
        $close = Setting::get($isWeekend ? 'weekend_close_time' : 'close_time') ?: $fallbackClose;

        return [
            Carbon::parse($date->toDateString() . ' ' . $open),
            Carbon::parse($date->toDateString() . ' ' . $close),
        ];
    }

    /** Start-time grid step — Settings -> Booking Slot Duration. */
    public function gridMinutes(): int
    {
        return (int) (Setting::get('slot_duration') ?: self::GRID_MINUTES);
    }

    /** Max bookings accepted for one day — Settings -> Max Bookings Per Day. 0 = unlimited. */
    public function dailyCap(): int
    {
        return (int) (Setting::get('max_bookings') ?: 0);
    }

    /** Active bookings already held for a date. */
    public function bookingsOnDate(string $date, ?int $excludeBookingId = null): int
    {
        return Booking::whereDate('booking_date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->count();
    }

    /**
     * Sum of durations (minutes) for the given service IDs — this is the
     * single block a multi-service booking occupies.
     */
    public function durationForServices(array $serviceIds): int
    {
        return (int) Service::whereIn('id', $serviceIds)->sum('duration');
    }

    /**
     * Create a booking, holding it to the capacity/hours rules.
     *
     * $attributes must include booking_date, booking_time, service_id, and
     * whatever else Booking::create() needs. Pass $durationMinutes when the
     * booking covers more than one service (the caller sums them) —
     * otherwise the primary service's own duration is used.
     *
     * @throws SlotUnavailableException
     */
    public function reserve(array $attributes, ?int $durationMinutes = null): Booking
    {
        return DB::transaction(function () use ($attributes, $durationMinutes) {
            // service_ids is not a bookings column; it drives the pivot below.
            $serviceIds = array_values(array_unique(array_map(
                'intval',
                $attributes['service_ids'] ?? []
            )));
            unset($attributes['service_ids']);

            // service_id stays populated with the first selection so any screen
            // still reading a single service keeps working.
            if ($serviceIds !== []) {
                $attributes['service_id'] = $attributes['service_id'] ?? $serviceIds[0];
            } elseif (! empty($attributes['service_id'])) {
                $serviceIds = [(int) $attributes['service_id']];
            }

            $duration = $durationMinutes
                ?? ($serviceIds !== [] ? $this->durationForServices($serviceIds) : null)
                ?? $attributes['duration']
                ?? $this->gridMinutes();

            if ($duration <= 0) {
                $duration = $this->gridMinutes();
            }

            $start = Carbon::parse($attributes['booking_date'] . ' ' . $attributes['booking_time']);
            $end   = $start->copy()->addMinutes($duration);

            $this->assertAvailable($attributes['booking_date'], $start, $end, null, lock: true);

            $attributes['duration']          = $duration;
            $attributes['reference_number'] ??= $this->nextReference();

            $booking = Booking::create($attributes);

            // Inside the same transaction, so a failure here rolls the booking
            // back rather than leaving one with no services attached.
            $this->attachServices($booking, $serviceIds);

            return $booking;
        });
    }

    /**
     * Attach services, snapshotting price and duration as they are now so a
     * later price change cannot rewrite what this booking cost.
     */
    public function attachServices(Booking $booking, array $serviceIds): void
    {
        if ($serviceIds === []) {
            return;
        }

        $pivot = Service::whereIn('id', $serviceIds)->get()
            ->mapWithKeys(fn (Service $s) => [
                $s->id => ['price' => $s->price, 'duration' => $s->duration],
            ])
            ->all();

        $booking->services()->sync($pivot);
    }

    /**
     * Re-validate capacity for an existing booking being moved to a new
     * date/time/duration (the booking's own current slot is excluded from
     * the count, so it never conflicts with itself).
     *
     * @throws SlotUnavailableException
     */
    public function reschedule(Booking $booking, string $date, string $time, int $durationMinutes): void
    {
        DB::transaction(function () use ($booking, $date, $time, $durationMinutes) {
            $start = Carbon::parse($date . ' ' . $time);
            $end   = $start->copy()->addMinutes($durationMinutes);

            $this->assertAvailable($date, $start, $end, $booking->id, lock: true);
        });
    }

    /**
     * @throws SlotUnavailableException
     */
    public function assertAvailable(string $date, Carbon $start, Carbon $end, ?int $excludeBookingId = null, bool $lock = false): void
    {
        if ($this->maxOverlap($date, $start, $end, $excludeBookingId, $lock) >= self::MECHANIC_CAPACITY) {
            throw new SlotUnavailableException(
                'That time slot is fully booked. Please choose another time.'
            );
        }

        // Shop-wide daily limit (Settings -> Max Bookings Per Day). Checked after
        // the slot test so the more specific message wins when both apply.
        $cap = $this->dailyCap();

        if ($cap > 0 && $this->bookingsOnDate($date, $excludeBookingId) >= $cap) {
            throw new SlotUnavailableException(
                'We\'re fully booked on that date. Please choose another day.'
            );
        }
    }

    /**
     * Every 30-minute grid start on $date for a booking of $durationMinutes,
     * with remaining capacity and whether it's still bookable (capacity left
     * and, for today, not already in the past).
     *
     * @return list<array{start: string, label: string, remaining: int, bookable: bool}>
     */
    public function dayAvailability(string $date, int $durationMinutes): array
    {
        $day = Carbon::parse($date)->startOfDay();
        [$open, $close] = $this->hoursFor($day);
        $isToday = $day->isToday();
        $now     = now();

        $slots = [];

        for ($slotStart = $open->copy(); $slotStart->copy()->addMinutes($durationMinutes)->lte($close); $slotStart->addMinutes($this->gridMinutes())) {
            $start = $slotStart->copy();
            $end   = $start->copy()->addMinutes($durationMinutes);

            $overlap   = $this->maxOverlap($date, $start, $end);
            $remaining = max(0, self::MECHANIC_CAPACITY - $overlap);
            $isPast    = $isToday && $start->lte($now);

            $slots[] = [
                'start'     => $start->format('H:i'),
                'label'     => $start->format('g:i A'),
                'remaining' => $remaining,
                'bookable'  => $remaining > 0 && ! $isPast,
            ];
        }

        return $slots;
    }

    /**
     * Every day in $year-$month: green ('open') if any grid start is
     * bookable, red ('full') otherwise; days before today are 'past'.
     *
     * @return list<array{date: string, status: string, bookable: bool}>
     */
    public function monthAvailability(int $year, int $month, int $durationMinutes): array
    {
        $firstDay    = Carbon::create($year, $month, 1)->startOfDay();
        $daysInMonth = $firstDay->daysInMonth;
        $today       = Carbon::today();

        $days = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = $firstDay->copy()->day($d);
            $dateStr = $date->toDateString();

            if ($date->lt($today)) {
                $days[] = ['date' => $dateStr, 'status' => 'past', 'bookable' => false];
                continue;
            }

            $anyBookable = collect($this->dayAvailability($dateStr, $durationMinutes))
                ->contains('bookable', true);

            $days[] = [
                'date'     => $dateStr,
                'status'   => $anyBookable ? 'open' : 'full',
                'bookable' => $anyBookable,
            ];
        }

        return $days;
    }

    public function nextReference(): string
    {
        $n = (Booking::withTrashed()->max('id') ?? 0) + 1;

        return 'BK-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * The true max concurrency of *existing* active bookings at any instant
     * within [$start, $end) on $date — half-open, so a booking ending
     * exactly at $start doesn't count.
     *
     * Concurrency only changes at a booking's start, so sampling at every
     * existing-booking start point inside the window (plus $start itself)
     * is enough to find the maximum — no need to walk every minute.
     */
    private function maxOverlap(string $date, Carbon $start, Carbon $end, ?int $excludeId = null, bool $lock = false): int
    {
        $intervals = $this->activeIntervalsForDate($date, $excludeId, $lock)
            ->filter(fn (array $iv) => $iv['start']->lt($end) && $iv['end']->gt($start));

        if ($intervals->isEmpty()) {
            return 0;
        }

        $points = $intervals->pluck('start')
            ->filter(fn (Carbon $t) => $t->gte($start) && $t->lt($end))
            ->push($start->copy())
            ->unique(fn (Carbon $t) => $t->timestamp);

        $max = 0;

        foreach ($points as $t) {
            $count = $intervals->filter(
                fn (array $iv) => $iv['start']->lte($t) && $iv['end']->gt($t)
            )->count();

            $max = max($max, $count);
        }

        return $max;
    }

    /**
     * @return Collection<int, array{start: Carbon, end: Carbon}>
     */
    /**
     * The booking, if any, that would clash with putting this mechanic on the
     * given window. Uses the same interval-overlap rule as capacity, so the
     * two cannot drift apart.
     *
     * Returns the clashing booking rather than a boolean so the caller can say
     * which one it is.
     */
    public function staffConflict(
        int $staffId,
        string $date,
        Carbon $start,
        Carbon $end,
        ?int $excludeBookingId = null
    ): ?Booking {
        return Booking::with('service:id,duration')
            ->where('booking_date', $date)
            ->where('staff_id', $staffId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->get()
            ->first(function (Booking $other) use ($date, $start, $end) {
                $otherStart = Carbon::parse($date.' '.$other->booking_time);
                $otherEnd   = $otherStart->copy()->addMinutes(
                    $other->duration ?? optional($other->service)->duration ?? $this->gridMinutes()
                );

                return $otherStart->lt($end) && $otherEnd->gt($start);
            });
    }

    /** The window a booking occupies, for the checks above. */
    public function windowFor(string $date, string $time, int $durationMinutes): array
    {
        $start = Carbon::parse($date.' '.$time);

        return [$start, $start->copy()->addMinutes($durationMinutes)];
    }

    private function activeIntervalsForDate(string $date, ?int $excludeId = null, bool $lock = false): Collection
    {
        $query = Booking::with('service:id,duration')
            ->where('booking_date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get(['id', 'service_id', 'booking_time', 'duration'])
            ->map(function (Booking $booking) use ($date) {
                $start   = Carbon::parse($date . ' ' . $booking->booking_time);
                $minutes = $booking->duration ?? optional($booking->service)->duration ?? $this->gridMinutes();

                return ['start' => $start, 'end' => $start->copy()->addMinutes($minutes)];
            });
    }
}
