<?php

namespace App\Jobs;

use App\Mail\AlertRecoveredMail;
use App\Models\AlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAlertRecoveredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly AlertRule $rule,
        public readonly float $value,
    ) {}

    public function handle(): void
    {
        $this->rule->loadMissing('recipients.user');

        foreach ($this->rule->recipients as $recipient) {
            if ($recipient->user?->email) {
                Mail::to($recipient->user->email)->send(new AlertRecoveredMail($this->rule, $this->value));
            }
        }
    }
}
