<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Contracts\Core\ReceivesEvents;
use Pterodactyl\Events\Event;
use Pterodactyl\Events\Server\Installed;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

class ServerInstalled extends Notification implements ReceivesEvents, ShouldQueue
{
    use Queueable;

    public Server $server;

    public User $user;

    /**
     * Handle a direct call to this notification from the server installed event. This is configured
     * in the event service provider.
     *
     * @phpstan-param Installed $event
     */
    public function handle(Event|Installed $event): void
    {
        $event->server->loadMissing('user');

        $this->server = $event->server;
        $this->user = $event->server->user;

        $this->user->notifyNow($this);
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
            ->greeting('Hello '.$this->user->username.'.')
            ->line('Your server has finished installing and is now ready for you to use.')
            ->line('Server Name: '.$this->server->name)
            ->action('Login and Begin Using', route('index'));
    }
}
