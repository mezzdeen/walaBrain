<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Proves the configured mailer can actually deliver.
 *
 * Queued like every other mail in the module, which is the point: a message
 * that arrives proves the transport *and* the worker, so the two halves are
 * never verified separately from how production sends mail.
 *
 * The body repeats the settings that sent it. Inboxes outlive `.env` edits, so
 * a message on its own would say only that delivery worked at some point, not
 * which configuration made it work.
 */
class MailDeliveryCheck extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{mailer: string, host: string|null, port: int|string|null, from: string|null}  $settings
     */
    public function __construct(
        public array $settings,
        public string $requestedAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('core::diagnostics.mail.subject', ['app' => config('app.name')]))
            ->line(__('core::diagnostics.mail.intro', ['app' => config('app.name')]))
            ->line(__('core::diagnostics.mail.requested_at', ['at' => $this->requestedAt]))
            ->line(__('core::diagnostics.mail.transport', [
                'mailer' => $this->settings['mailer'],
                'host' => $this->settings['host'] ?? '—',
                'port' => $this->settings['port'] ?? '—',
            ]))
            ->line(__('core::diagnostics.mail.from', ['from' => $this->settings['from'] ?? '—']))
            ->line(__('core::diagnostics.mail.ignore'));
    }
}
