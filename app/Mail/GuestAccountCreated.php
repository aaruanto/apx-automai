<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set Up Your APX AutoMai Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.guest-account-created',
            with: [
                'user'     => $this->user,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}