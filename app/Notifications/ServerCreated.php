<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ServerCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $server;

    /**
     * Create a new notification instance.
     *
     * @param  array $server
     * @return void
     */
    public function __construct(array $server)
    {
        $this->server = (object) $server;
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
                    ->line('A new server as been assigned to your account.')
                    ->line('Server Name: ' . $this->server->name)
                    ->line('Memory: ' . $this->server->memory . ' MB')
                    ->line('Node: ' . $this->server->node)
                    ->line('Type: ' . $this->server->service . ' - ' . $this->server->option)
                    ->action('Peel Off the Protective Wrap', route('server.index', $this->server->uuidShort))
                    ->line('Please let us know if you have any additional questions or concerns!');
    }

}
