<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The acknowledgment, sent the moment a booking request is saved.
 *
 * This is a receipt, not a ticket. It deliberately carries no reference
 * number, in the subject or the body: the booking is still 'pending' when
 * this goes out, and a number printed next to "bring this when you arrive"
 * told the customer they had an appointment before the shop had agreed to
 * one. Customers turned up on the strength of it. The reference is disclosed
 * by BookingConfirmed and nowhere earlier — see that mailable's comment.
 *
 * The reference still exists on the row from creation (BookingAvailability
 * generates it inside reserve()'s transaction, and every admin screen and
 * audit label reads it). Withholding it is a rule about output, so it has to
 * be kept at each place that writes to the customer, not assumed.
 */
class BookingReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We have your booking request — APX AUTOMAI',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-received',
            with: ['booking' => $this->booking],
        );
    }
}
