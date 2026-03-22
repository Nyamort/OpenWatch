<?php

namespace App\Mail;

use App\Models\AlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertTriggeredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AlertRule $rule,
        public readonly float $value,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[ALERT] {$this->rule->name} triggered");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alert-triggered');
    }
}
