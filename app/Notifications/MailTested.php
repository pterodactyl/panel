<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail()
    {
        return (new MailMessage())
            ->subject('Pterodactyl Test Message')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('This is a test of the Pterodactyl mail system. You\'re good to go!');
    }
}
