<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddedToServer extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array{user: string, name: string, uuidShort: string}  $server
     */
    public function __construct(
        public readonly array $server,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.added_to_server.subject'))
            ->greeting(__('notifications.added_to_server.greeting', ['name' => $this->server['user']]))
            ->line(__('notifications.added_to_server.intro'))
            ->line(__('notifications.added_to_server.server', ['server' => $this->server['name']]))
            ->action(__('notifications.added_to_server.action'), url('/server/'.$this->server['uuidShort']));
    }
}
