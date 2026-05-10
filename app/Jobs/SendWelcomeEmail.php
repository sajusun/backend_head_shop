<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(new WelcomeMail($this->user));
        } catch (\Throwable $e) {
            Log::error("SendWelcomeEmail failed for user {$this->user->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
