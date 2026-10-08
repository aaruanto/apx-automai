<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * One in-app notification about a booking.
 *
 * All five booking events share a shape — title, body, icon, link — so they
 * share a class rather than five near-identical ones. The event name is kept
 * on the payload so the UI can style or filter by it later.
 *
 * Database channel only, deliberately: the brief scopes SMS and email out of
 * this, and mail is not configured yet in any case.
 */
class BookingNotification extends Notification
{
    use Queueable;

    public const BOOKING_REQUESTED = 'booking_requested';
    public const BOOKING_CONFIRMED = 'booking_confirmed';
    public const BOOKING_CANCELLED = 'booking_cancelled';
    public const BOOKING_RESCHEDULED = 'booking_rescheduled';
    public const BOOKING_COMPLETED = 'booking_completed';

    private const ICONS = [
        self::BOOKING_REQUESTED   => 'fa-calendar-plus',
        self::BOOKING_CONFIRMED   => 'fa-circle-check',
        self::BOOKING_CANCELLED   => 'fa-ban',
        self::BOOKING_RESCHEDULED => 'fa-calendar-day',
        self::BOOKING_COMPLETED   => 'fa-flag-checkered',
    ];

    public function __construct(
        private string $event,
        private Booking $booking,
        private string $title,
        private string $body,
        private bool $forAdmin = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event'     => $this->event,
            'title'     => $this->title,
            'body'      => $this->body,
            'icon'      => self::ICONS[$this->event] ?? 'fa-bell',
            'reference' => $this->booking->reference_number,
            // Admins land on the booking list, customers on their own
            // dashboard: a customer has no access to the admin routes.
            'link'      => $this->forAdmin
                ? route('admin.bookings.index')
                : route('customer.dashboard'),
        ];
    }

    // ── Builders, so callers do not assemble copy at each call site ──────

    public static function requested(Booking $booking): self
    {
        return new self(
            self::BOOKING_REQUESTED,
            $booking,
            'New booking request',
            ($booking->user->name ?? 'A customer').' requested '.$booking->service_list
                .' on '.$booking->booking_date.' at '.$booking->booking_time.'.',
            forAdmin: true,
        );
    }

    public static function confirmed(Booking $booking): self
    {
        return new self(
            self::BOOKING_CONFIRMED,
            $booking,
            'Booking confirmed',
            'Your booking '.$booking->reference_number.' for '.$booking->service_list
                .' on '.$booking->booking_date.' is confirmed.',
        );
    }

    public static function cancelled(Booking $booking, ?string $reason = null): self
    {
        $reason = trim((string) ($reason ?? $booking->cancel_reason));

        return new self(
            self::BOOKING_CANCELLED,
            $booking,
            'Booking cancelled',
            'Your booking '.$booking->reference_number.' on '.$booking->booking_date
                .' has been cancelled.'
                .($reason !== '' ? ' Reason: '.$reason : ''),
        );
    }

    public static function rescheduled(Booking $booking, string $oldDate, string $oldTime): self
    {
        return new self(
            self::BOOKING_RESCHEDULED,
            $booking,
            'Booking rescheduled',
            'Your booking '.$booking->reference_number.' has moved from '
                .$oldDate.' at '.$oldTime.' to '
                .$booking->booking_date.' at '.$booking->booking_time.'.',
        );
    }

    public static function completed(Booking $booking): self
    {
        return new self(
            self::BOOKING_COMPLETED,
            $booking,
            'Service completed',
            'Your booking '.$booking->reference_number.' for '.$booking->service_list
                .' is complete. Thank you for choosing APX.',
        );
    }
}
