<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Pterodactyl\Models\Server;

class ServerInstalled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \Pterodactyl\Models\Server
     */
    public $server;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->greeting('Hello ' . $this->server->user . '.')
            ->line('Your server has finished installing. You can now use it.')
            ->line('Server Name: ' . $this->server->name)
            ->action('Visit Panel', route('index'));
    }
}
