<?php

namespace App\Modules\Flows\Notifications;

use App\Modules\Boards\Models\Node;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A flow has put work on your list.
 */
class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(private readonly Node $task) {}

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
            'key' => 'flows.notifications.task_assigned',
            'params' => [
                'title' => $this->task->title,
                'due' => (string) $this->task->due_date?->toDateString(),
            ],
            'url' => route('my-work.index', absolute: false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('flows::flows.mail.task_subject', ['title' => $this->task->title]))
            ->line(__('flows::flows.mail.task_line', [
                'title' => $this->task->title,
                'due' => (string) $this->task->due_date?->toDateString(),
            ]))
            ->action(__('flows::flows.mail.task_action'), route('my-work.index'));
    }
}
