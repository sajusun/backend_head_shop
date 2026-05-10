<?php

namespace App\Jobs;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Order $order,
        public string $newStatus
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->order->email)
                ->send(new OrderStatusMail($this->order->load('items.product'), $this->newStatus));
        } catch (\Throwable $e) {
            Log::error("SendOrderStatusEmail failed for order #{$this->order->order_number}: " . $e->getMessage());
            throw $e;
        }
    }
}
