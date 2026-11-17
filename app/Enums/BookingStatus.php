<?php

namespace App\Enums;

/**
 * Where a booking is, and everything that follows from it.
 *
 * This replaces four constants on App\Models\Booking and six copies of the
 * label-and-badge map: two in admin/bookings/index.blade.php (one in PHP at
 * the table, one in JavaScript for the detail modal), two in
 * admin/bookings/schedule.blade.php, one in the customer dashboard, and a
 * private STATUS_LABELS on Admin\BookingController that disagreed with all of
 * them — it rendered 'in_progress' as "already in progress", which reads as a
 * sentence fragment wherever it was used as a label rather than inside the
 * one error message it was written for.
 *
 * They had already drifted in a way a customer could see. None of the admin
 * maps had an entry for 'completed', so `$statusMap[$b->status] ?? ...` fell
 * through to the default and a finished job wore an amber Pending badge on the
 * bookings list.
 *
 * So the ladder, the labels, the badge classes and the transition rules are
 * one thing in one file, and the Blade and JavaScript maps are generated from
 * it. A seventh copy is now a thing you have to go out of your way to write.
 *
 * ── On Cancelled being in here ──────────────────────────────────────────
 *
 * It is a value the column holds, so it has to have a label and a badge like
 * any other. It is deliberately NOT on the ladder: order() returns null for
 * it, ladder() leaves it out, and canMoveTo() therefore refuses every move
 * into or out of it. Cancelling is its own action because it demands a reason,
 * and that is unchanged — BookingCancellation still owns it, cancel_reason /
 * cancelled_by / cancelled_at still record who and why.
 */
enum BookingStatus: string
{
    case PendingReview     = 'pending_review';
    case BookingConfirmed  = 'booking_confirmed';
    case Arrived           = 'arrived';
    case VehicleInspection = 'vehicle_inspection';
    case ServiceInProgress = 'service_in_progress';
    case ServiceCompleted  = 'service_completed';

    /** Outside the ladder. See the note above. */
    case Cancelled = 'cancelled';

    /**
     * The order work actually happens in.
     *
     * A booking moves forward through these and never back: a completed job
     * returning to pending re-enters the active pipeline and quietly leaves
     * the revenue figures. Skipping ahead is allowed — a walk-in can go
     * straight from Pending Review to Service Completed.
     *
     * @return list<self>
     */
    public static function ladder(): array
    {
        return [
            self::PendingReview,
            self::BookingConfirmed,
            self::Arrived,
            self::VehicleInspection,
            self::ServiceInProgress,
            self::ServiceCompleted,
        ];
    }

    /** What the customer and the shop read. */
    public function label(): string
    {
        return match ($this) {
            self::PendingReview     => 'Pending Review',
            self::BookingConfirmed  => 'Booking Confirmed',
            self::Arrived           => 'Arrived',
            self::VehicleInspection => 'Vehicle Inspection',
            self::ServiceInProgress => 'Service In Progress',
            self::ServiceCompleted  => 'Service Completed',
            self::Cancelled         => 'Cancelled',
        };
    }

    /**
     * The badge class, as a name rather than a colour.
     *
     * What each one looks like is a stylesheet's business, and they
     * legitimately differ: badge-completed is muted grey on the customer
     * dashboard, where a finished booking is history, and green on the admin
     * side, where it is the outcome. One class name, three stylesheets
     * (the admin layout, dashboard.css, customer-dashboard.css).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingReview     => 'badge-pending',
            self::BookingConfirmed  => 'badge-confirmed',
            self::Arrived           => 'badge-arrived',
            self::VehicleInspection => 'badge-inspection',
            self::ServiceInProgress => 'badge-inprogress',
            self::ServiceCompleted  => 'badge-completed',
            self::Cancelled         => 'badge-cancelled',
        };
    }

    /** Position on the ladder, 1-based, or null for a status outside it. */
    public function order(): ?int
    {
        $at = array_search($this, self::ladder(), true);

        return $at === false ? null : $at + 1;
    }

    public function onLadder(): bool
    {
        return $this->order() !== null;
    }

    /**
     * Whether the status may move from here to $target.
     *
     * Forward only, skipping allowed, and a status outside the ladder —
     * Cancelled — is neither reachable nor escapable this way.
     */
    public function canMoveTo(self $target): bool
    {
        $from = $this->order();
        $to   = $target->order();

        if ($from === null || $to === null) {
            return false;
        }

        return $to >= $from;
    }

    /**
     * The next step, or null if there is none.
     *
     * One step, not a jump: this is what the queue board's Advance control
     * uses, and a control that moves a booking two places because the ladder
     * changed shape is worse than one that does nothing. Cancelled has no
     * next step, and neither does Service Completed.
     */
    public function next(): ?self
    {
        $at = $this->order();

        return $at === null ? null : (self::ladder()[$at] ?? null);
    }

    /** Whether this booking is finished and its record should stand. */
    public function isLocked(): bool
    {
        return $this === self::ServiceCompleted;
    }

    /**
     * Whether it may still be cancelled.
     *
     * Anything before Service Completed. A finished job is a record of work
     * done — cancelling it would remove revenue from the reports with nothing
     * left to explain the gap — and an already-cancelled booking has nothing
     * to cancel.
     */
    public function isCancellable(): bool
    {
        return $this->onLadder() && ! $this->isLocked();
    }

    /**
     * Whether "Arrived" is allowed right now.
     *
     * Status is the only condition: bookings are made ahead of time, so the
     * appointment date says nothing about whether the customer is standing at
     * the counter now. The shop decides that, not the calendar.
     */
    public function isStartable(): bool
    {
        return in_array($this, [self::PendingReview, self::BookingConfirmed], true);
    }

    /**
     * Whether this booking still occupies a slot in the shop.
     *
     * Everything on the ladder except the finished end of it. Cancelled frees
     * its slot immediately, which is the point of cancelling.
     *
     * Deliberately a separate question from isCancellable(), which currently
     * has the same answer: one is about capacity and the other is about what
     * the shop may still do, and there is no reason the two must stay equal.
     */
    public function occupiesSlot(): bool
    {
        return $this->onLadder() && ! $this->isLocked();
    }

    /**
     * Which of the customer dashboard's four tabs this falls under.
     *
     * The customer does not need six tabs. They need to know what is coming
     * up, what is happening now, what is done and what is not happening — so
     * the six-step ladder is grouped for the filters while the card itself
     * still shows the real label(). A customer whose car is being inspected
     * should read "Vehicle Inspection", not "In Progress".
     */
    public function customerGroup(): string
    {
        return match ($this) {
            self::PendingReview, self::BookingConfirmed => 'upcoming',
            self::Arrived, self::VehicleInspection, self::ServiceInProgress => 'in_progress',
            self::ServiceCompleted => 'completed',
            self::Cancelled        => 'cancelled',
        };
    }

    /**
     * Every status as plain arrays, for serialising into a page.
     *
     * The views that need the map in JavaScript are handed this instead of
     * retyping it. Keyed by the stored value, because that is what a
     * data-status attribute and a filter <select> carry.
     *
     * @return array<string, array{label: string, badge: string, order: int|null, next: string|null}>
     */
    public static function forJs(): array
    {
        $map = [];

        foreach (self::cases() as $case) {
            $map[$case->value] = [
                'label' => $case->label(),
                'badge' => $case->badgeClass(),
                'order' => $case->order(),
                'next'  => $case->next()?->value,
            ];
        }

        return $map;
    }

    /**
     * The stored values, for a validation rule or a whereIn.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return list<string> */
    public static function ladderValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::ladder());
    }

    /** @return list<string> */
    public static function occupyingValues(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            array_values(array_filter(self::ladder(), fn (self $case) => $case->occupiesSlot()))
        );
    }

    /** @return list<string> */
    public static function startableValues(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            array_values(array_filter(self::cases(), fn (self $case) => $case->isStartable()))
        );
    }

    /** @return list<string> */
    public static function cancellableValues(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            array_values(array_filter(self::cases(), fn (self $case) => $case->isCancellable()))
        );
    }

    /**
     * The labels keyed by value, for a <select> or a Blade @foreach.
     *
     * @return array<string, string>
     */
    public static function ladderOptions(): array
    {
        $options = [];

        foreach (self::ladder() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
