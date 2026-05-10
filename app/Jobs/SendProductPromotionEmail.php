<?php

namespace App\Jobs;

use App\Mail\ProductPromotionMail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendProductPromotionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public User $user,
        public Product $product,
        public string $promoMessage
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(
                new ProductPromotionMail($this->product, $this->promoMessage, $this->user->name)
            );
        } catch (\Throwable $e) {
            Log::error("SendProductPromotionEmail failed for user {$this->user->email}, product {$this->product->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
