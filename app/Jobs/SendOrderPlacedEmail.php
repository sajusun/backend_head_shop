<?php

namespace App\Jobs;

use App\Mail\OrderPlacedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderPlacedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        try {
            Mail::to($this->order->email)
                ->send(new OrderPlacedMail($this->order->load('items.product')));
        } catch (\Throwable $e) {
            Log::error("SendOrderPlacedEmail failed for order #{$this->order->order_number}: " . $e->getMessage());
            throw $e;
        }
    }
}
