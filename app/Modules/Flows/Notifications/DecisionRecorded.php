<?php

namespace App\Modules\Flows\Notifications;

use App\Modules\Boards\Models\Node;
use App\Modules\Flows\Enums\ApprovalStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something was decided about a request you submitted.
 *
 * Carries the comment where one was required, because "changes requested"
 * without what to change is a phone call waiting to happen.
 */
class DecisionRecorded extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Node $node,
        private readonly ApprovalStatus $decision,
        private readonly ?string $comment,
    ) {}

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
            'key' => 'flows.notifications.decision_'.$this->decision->value,
            'params' => [
                'reference' => (string) $this->node->reference,
                'comment' => (string) $this->comment,
            ],
            'url' => route('nodes.show', $this->node, absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reference = (string) $this->node->reference;

        $mail = (new MailMessage)
            ->subject(__('flows::flows.mail.decision_subject', ['reference' => $reference]))
            ->line(__('flows::flows.mail.decision_'.$this->decision->value, ['reference' => $reference]));

        if ($this->comment !== null && $this->comment !== '') {
            $mail->line(__('flows::flows.mail.decision_comment', ['comment' => $this->comment]));
        }

        return $mail->action(__('flows::flows.mail.decision_action'), route('nodes.show', $this->node));
    }
}
