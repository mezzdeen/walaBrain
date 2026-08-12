<?php

namespace App\Modules\Flows\Notifications;

use App\Modules\Flows\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A decision is waiting on you.
 *
 * The email deep-links straight to the decision screen, so an approver working
 * from their inbox acts in one click. The in-app copy stores a message key and
 * parameters rather than prose, so the notification centre renders it in
 * whatever language its reader has chosen — prose frozen at send time would be
 * frozen in the sender's language instead.
 */
class ApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(private readonly Approval $approval) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'key' => 'flows.notifications.approval_requested',
            'params' => ['reference' => (string) $this->approval->node->reference],
            'url' => route('approvals.show', $this->approval, absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reference = (string) $this->approval->node->reference;

        return (new MailMessage)
            ->subject(__('flows::flows.mail.approval_subject', ['reference' => $reference]))
            ->line(__('flows::flows.mail.approval_line', ['reference' => $reference]))
            ->action(__('flows::flows.mail.approval_action'), route('approvals.show', $this->approval));
    }
}
