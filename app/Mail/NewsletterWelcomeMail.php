<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $subscriberEmail) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're subscribed to HERD SHOP Newsletter! 📬",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
