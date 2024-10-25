<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AddedToServer extends Notification implements ShouldQueue
{
    use Queueable;

    public object $server;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $server)
    {
        $this->server = (object) $server;
    }

    /**
     * Get the notification's delivery channels.
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
        return (new MailMessage())
            ->greeting('Hallo ' . $this->server->user . '!')
            ->line('Du wurdest als Unterbenutzer für den folgenden Server hinzugefügt, wodurch du ab jetzt eine gewisse Kontrolle über den Server hast.')
            ->line('Servername: ' . $this->server->name)
            ->action('Panel aufrufen', url('/server/' . $this->server->uuidShort));
    }
}
