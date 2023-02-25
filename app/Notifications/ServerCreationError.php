<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServerCreationError extends Notification
{
    use Queueable;

    /**
     * @var Server
     */
    private $server;

    /**
     * Create a new notification instance.
     *
     * @param  Server  $server
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => __('Server Creation Error'),
            'content' => "
                <p>Hello <strong>{$this->server->User->name}</strong>, An unexpected error has occurred...</p>
                <p>There was a problem creating your server on our pterodactyl panel. There are likely no allocations or rooms left on the selected node. Please contact one of our support members through our discord server to get this resolved asap!</p>
                <p>We thank you for your patience and our deepest apologies for this inconvenience.</p>
                <p>".config('app.name', 'Laravel').'</p>
            ',
        ];
    }
}
