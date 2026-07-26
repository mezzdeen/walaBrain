<?php

namespace App\Modules\Core\Notifications;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an existing user they have been made the owner of an organization.
 *
 * Informational rather than an invitation: the membership is already in place
 * by the time this goes out, so there is nothing for the recipient to accept.
 */
class OrganizationOwnershipGranted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Organization $organization)
    {
        // The organization is created inside a transaction, so the job must not
        // be released to the queue until that transaction commits.
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('core::invitations.granted.subject', ['name' => $this->organization->name]))
            ->greeting(__('core::invitations.greeting', ['name' => $notifiable->full_name]))
            ->line(__('core::invitations.granted.intro', ['name' => $this->organization->name]))
            ->line(__('core::invitations.granted.scope'))
            ->action(__('core::invitations.granted.action'), route('dashboard'));
    }
}
