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
            ->greeting('Hello '.$this->server['user'].'!')
            ->line('You have been added as a subuser for the following server, allowing you certain control over the server.')
            ->line('Server Name: '.$this->server['name'])
            ->action('Visit Server', url('/server/'.$this->server['uuidShort']));
    }
}
