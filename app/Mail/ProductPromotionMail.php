<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductPromotionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public string $promoMessage,
        public string $recipientName = 'Valued Customer'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔥 Special Offer: {$this->product->name} — HERD SHOP",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-promotion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
