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
 * globally, because two of the six would be wrong:
 *
 *   requested    goes to admins, who watch the dashboard. Mailing every admin
 *                on every request is noise, and the bell already has it.
 *   confirmed    is ALREADY emailed, by Admin\BookingController sending the
 *                BookingConfirmed mailable. Adding mail here would send two.
 *
 * completed used to sit in that list, on the grounds that it "tells a customer
 * something they learned at the counter ten minutes earlier". That holds only
 * for a customer who waited. Someone who dropped the vehicle off and left has
 * no way to know the work is done, and the bell reaches them only if they
 * happen to open the app — so the one person the message exists for was the
 * one person not getting it. It is mailed now, and texted from
 * Admin\BookingController beside the confirmation text.
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
    public const RESCHEDULE_PROPOSED = 'booking_reschedule_proposed';
    public const BOOKING_COMPLETED = 'booking_completed';

    private const ICONS = [
        self::BOOKING_REQUESTED   => 'fa-calendar-plus',
        self::BOOKING_CONFIRMED   => 'fa-circle-check',
        self::BOOKING_CANCELLED   => 'fa-ban',
        self::BOOKING_RESCHEDULED => 'fa-calendar-day',
        self::RESCHEDULE_PROPOSED => 'fa-calendar-check',
        self::BOOKING_COMPLETED   => 'fa-flag-checkered',
    ];

    public function __construct(
        private string $event,
        private Booking $booking,
        private string $title,
        private string $body,
        private bool $forAdmin = false,
        private bool $disclosesReference = true,
    ) {
    }

    /**
     * Events that also go out by email. See the class comment for why the
     * other two do not.
     */
    private const MAILED = [
        self::BOOKING_CANCELLED,
        self::BOOKING_RESCHEDULED,
        self::RESCHEDULE_PROPOSED,
        self::BOOKING_COMPLETED,
    ];

    /**
     * Admin-created walk-ins get a placeholder address so the account can hold
     * bookings; nobody reads it. Mailing it fails slowly against a real SMTP
     * server. The same suffix BookingConfirmed already skips.
     */
    public const PLACEHOLDER_DOMAIN = '@apxautomai.local';

    public function via(object $notifiable): array
    {
        // database first, and the order matters: Laravel walks this list in
        // sequence, so the bell entry is written before mail is attempted and
        // survives a mail failure.
        $channels = ['database'];

        if (in_array($this->event, self::MAILED, true) && self::hasRealAddress($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Public and static so a caller sending its own mailable about a booking
     * applies the same rule rather than writing a second copy of it. The admin
     * create path needs exactly this test before sending BookingReceived.
     */
    public static function hasRealAddress(?object $notifiable): bool
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
        $heading = match ($this->event) {
            self::BOOKING_CANCELLED  => 'Booking Cancelled',
            self::RESCHEDULE_PROPOSED => 'A Change To Your Booking',
            // Says the thing the customer needs, not the thing that happened
            // to the record. "Booking Completed" is the status; what they
            // want from the subject line is that the car can be collected.
            self::BOOKING_COMPLETED  => 'Your Vehicle Is Ready',
            default                  => 'Booking Rescheduled',
        };

        return (new MailMessage)
            ->subject($heading.' — APX AUTOMAI #'.$this->booking->reference_number)
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
        $data = [
            'event' => $this->event,
            'title' => $this->title,
            'body'  => $this->body,
            'icon'  => self::ICONS[$this->event] ?? 'fa-bell',
            // Admins land on the booking list, customers on their own
            // dashboard: a customer has no access to the admin routes.
            'link'  => $this->forAdmin
                ? route('admin.bookings.index')
                : route('customer.dashboard'),
        ];

        // Omitted for the one notification that fires before an admin has
        // confirmed anything — see received(). linkFor() builds its ?ref=
        // deep link out of this key, so leaving it out also keeps the
        // reference out of the URL the customer is handed. Only the admin
        // bookings index reads ?ref= anyway, so a customer loses nothing.
        if ($this->disclosesReference) {
            $data['reference'] = $this->booking->reference_number;
        }

        return $data;
    }

    /**
     * Where a stored notification actually points.
     *
     * The link in the payload is the bare list URL: "a booking was cancelled"
     * dropped the admin at the top of every booking there has ever been, with
     * nothing to say which one it meant. The reference has always been stored
     * beside it, so the destination can name the booking.
     *
     * Resolved here at read time rather than written into the payload, because
     * every notification already in the table was written with the old link
     * and those are the ones an admin is reading today. Computing it from
     * 'reference' fixes the history as well as the next one sent.
     */
    public static function linkFor(array $data): ?string
    {
        $link = $data['link'] ?? null;
        $ref  = $data['reference'] ?? null;

        if (! $link || ! $ref || str_contains($link, 'ref=')) {
            return $link;
        }

        return $link.(str_contains($link, '?') ? '&' : '?').'ref='.urlencode($ref);
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

    /**
     * The customer's copy of requested().
     *
     * requested() is worded for the admins who receive it — "A customer
     * requested…" — and carries forAdmin, so it cannot simply be sent to the
     * customer as well. When the shop books on someone's behalf, the customer
     * did not choose the appointment and has nothing on screen telling them it
     * exists, so they get their own entry saying what was booked for them.
     *
     * Deliberately not added to MAILED. That list is keyed on the event, and
     * this shares BOOKING_REQUESTED with the admin notice, so mailing it would
     * mail every admin on every request — the noise the class comment above
     * rules out. The email is the existing BookingReceived mailable, sent at
     * the admin create site.
     *
     * The only customer-facing notification that fires while the booking is
     * still 'pending', so it is also the only one that must not name the
     * reference number — hence disclosesReference: false, which keeps it out
     * of the body, the payload and linkFor()'s URL alike. The customer is
     * given the number by BookingConfirmed and not before.
     */
    public static function received(Booking $booking): self
    {
        return new self(
            self::BOOKING_REQUESTED,
            $booking,
            'Booking received',
            'We have your request for '.$booking->service_list
                .' on '.$booking->booking_date.' at '.$booking->booking_time
                .'. It is pending review — we will confirm it shortly.',
            disclosesReference: false,
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

    /**
     * A change the shop is asking for, which has NOT happened.
     *
     * Worded so the customer knows their booking still stands as it is: the
     * old slot is still theirs until they agree, and doing nothing keeps it.
     */
    public static function rescheduleProposed(Booking $booking, \App\Models\RescheduleRequest $request): self
    {
        return new self(
            self::RESCHEDULE_PROPOSED,
            $booking,
            'A change to your booking',
            'We need to move your booking '.$booking->reference_number.' from '
                .$request->originalLabel().' to '.$request->proposedLabel().'. '
                // Punctuated here: the reason is whatever the admin typed and
                // usually has no full stop, so without this it ran straight
                // into the next sentence — "called in sick Nothing has changed".
                .'Reason: '.rtrim($request->reason, " .").'. '
                .'Nothing has changed yet — your original time still stands until you tell us. '
                .'Please reply or call us to confirm.',
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
