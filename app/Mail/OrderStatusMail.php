<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $newStatus
    ) {}

    public function envelope(): Envelope
    {
        $label = ucfirst($this->newStatus);
        return new Envelope(
            subject: "Order #{$this->order->order_number} Update: {$label} — HERD SHOP",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
