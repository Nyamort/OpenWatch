<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly OrganizationInvitation $invitation,
        private readonly string $rawToken,
        private readonly Organization $organization,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $registerUrl = url('/register?invitation='.$this->rawToken);

        return (new MailMessage)
            ->subject("You've been invited to {$this->organization->name}")
            ->line("You have been invited to join the **{$this->organization->name}** organization.")
            ->action('Create your account', $registerUrl)
            ->line('This invitation will expire in 7 days.')
            ->line('If you did not expect this invitation, you may ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
        ];
    }
}
