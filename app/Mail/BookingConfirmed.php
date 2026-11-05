<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The ticket.
 *
 * This is the FIRST and ONLY place the customer is given their reference
 * number. BookingReceived, sent at creation, withholds it on purpose: the
 * booking is still pending then, and a number handed out next to "bring this
 * when you arrive" told customers they had an appointment the shop had not
 * agreed to. Only an admin confirming a booking sends this, so the number
 * going out means the slot is really theirs.
 *
 * The reference stays in the subject deliberately — it is what the customer
 * searches their inbox for while standing at the counter.
 */
class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmed — APX AUTOMAI #' . $this->booking->reference_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-confirmed',
            with: ['booking' => $this->booking],
        );
    }
}