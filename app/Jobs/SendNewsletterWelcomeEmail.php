<?php

namespace App\Jobs;

use App\Mail\NewsletterWelcomeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNewsletterWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new NewsletterWelcomeMail($this->email));
        } catch (\Throwable $e) {
            Log::error("SendNewsletterWelcomeEmail failed for {$this->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
