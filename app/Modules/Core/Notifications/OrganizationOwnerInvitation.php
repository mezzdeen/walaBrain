<?php

namespace App\Modules\Core\Notifications;

use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Support\OrganizationInvitations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites an address with no account to register as an organization's owner.
 *
 * Carries the only copy of the plaintext token: the database holds nothing but
 * its hash, so this mail is what makes the invitation usable.
 */
class OrganizationOwnerInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public OrganizationInvitation $invitation,
        public string $plainToken,
    ) {
        // The invitation is written inside a transaction, so the job must not
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

    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->invitation->organization->name;

        return (new MailMessage)
            ->subject(__('core::invitations.invited.subject', ['name' => $organization]))
            ->line(__('core::invitations.invited.intro', ['name' => $organization]))
            ->line(__('core::invitations.invited.scope'))
            ->action(
                __('core::invitations.invited.action'),
                route('invitations.show', ['token' => $this->plainToken]),
            )
            ->line(__('core::invitations.invited.expires', [
                'days' => OrganizationInvitations::EXPIRES_AFTER_DAYS,
            ]))
            ->line(__('core::invitations.invited.ignore'));
    }
}
