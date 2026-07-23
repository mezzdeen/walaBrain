<?php

namespace App\Modules\Core\Notifications;

use App\Modules\Core\Models\OrganizationInvitation;
use App\Modules\Core\Support\OrganizationInvitations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites an address to join an organization as one of its members.
 *
 * The other half of {@see OrganizationOwnerInvitation}, worded for membership
 * rather than ownership: the invitee is joining a team, not taking charge of a
 * new tenant. Carries the only copy of the plaintext token, and links to the
 * same acceptance route — the invitee accepts whether or not they already have
 * an account, and the organization stays hidden from them until they do.
 */
class OrganizationMemberInvitation extends Notification implements ShouldQueue
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
            ->subject(__('core::invitations.member.subject', ['name' => $organization]))
            ->line(__('core::invitations.member.intro', ['name' => $organization]))
            ->line(__('core::invitations.member.scope'))
            ->action(
                __('core::invitations.member.action'),
                route('invitations.show', ['token' => $this->plainToken]),
            )
            ->line(__('core::invitations.member.expires', [
                'days' => OrganizationInvitations::EXPIRES_AFTER_DAYS,
            ]))
            ->line(__('core::invitations.member.ignore'));
    }
}
