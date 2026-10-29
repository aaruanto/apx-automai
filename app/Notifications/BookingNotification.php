<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One in-app notification about a booking.
 *
 * All five booking events share a shape — title, body, icon, link — so they
 * share a class rather than five near-identical ones. The event name is kept
 * on the payload so the UI can style or filter by it later.
 *
 * It was database-only, which meant a cancelled booking wrote a bell entry
 * and nothing else: a customer who does not open the app found out by turning
 * up at the shop. Email is now added per event — see MAILED — and not
 * globally, because two of the five would be wrong:
 *
 *   requested    goes to admins, who watch the dashboard. Mailing every admin
 *                on every request is noise, and the bell already has it.
 *   confirmed    is ALREADY emailed, by Admin\BookingController sending the
 *                BookingConfirmed mailable. Adding mail here would send two.
 *   completed    tells a customer something they learned at the counter ten
 *                minutes earlier.
 *
 * Deliberately not ShouldQueue. QUEUE_CONNECTION is database and nothing on
 * Render runs a worker, so a queued notification would sit in the jobs table
 * forever. It sends inline, and callers tolerate failure — see the try/catch
 * at the cancel site.
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

    /**
     * Events that also go out by email. See the class comment for why the
     * other two do not.
     */
    private const MAILED = [
        self::BOOKING_CANCELLED,
        self::BOOKING_RESCHEDULED,
    ];

    /**
     * Admin-created walk-ins get a placeholder address so the account can hold
     * bookings; nobody reads it. Mailing it fails slowly against a real SMTP
     * server. The same suffix BookingConfirmed already skips.
     */
    private const PLACEHOLDER_DOMAIN = '@apxautomai.local';

    public function via(object $notifiable): array
    {
        // database first, and the order matters: Laravel walks this list in
        // sequence, so the bell entry is written before mail is attempted and
        // survives a mail failure.
        $channels = ['database'];

        if (in_array($this->event, self::MAILED, true) && $this->hasRealAddress($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function hasRealAddress(object $notifiable): bool
    {
        $email = $notifiable->email ?? null;

        return is_string($email)
            && $email !== ''
            && ! str_ends_with($email, self::PLACEHOLDER_DOMAIN);
    }

    /**
     * The customer's plan changed, so this says what changed and what is true
     * now — not "an update is available, please sign in".
     *
     * The channel toggles under Message Templates are deliberately not read
     * here. They govern the scheduled reminder stream; this is transactional
     * mail about one booking, and the two existing transactional mailables
     * (BookingConfirmed, BookingReceived) do not consult them either. Someone
     * who switched reminders off still needs to know their appointment is off.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $heading = $this->event === self::BOOKING_CANCELLED
            ? 'Booking Cancelled'
            : 'Booking Rescheduled';

        return (new MailMessage)
            ->subject($heading.' — APX AutoMai #'.$this->booking->reference_number)
            ->markdown('emails.booking-notification', [
                'booking' => $this->booking,
                'heading' => $heading,
                'title'   => $this->title,
                'body'    => $this->body,
                'name'    => $notifiable->name ?? 'there',
            ]);
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
