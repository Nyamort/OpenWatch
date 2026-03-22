<?php

namespace App\Mail;

use App\Models\AlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertRecoveredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AlertRule $rule,
        public readonly float $value,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[RESOLVED] {$this->rule->name} recovered");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alert-recovered');
    }
}
