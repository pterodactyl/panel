<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject('Pterodactyl Test Message')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('This is a test of the Pterodactyl mail system. You\'re good to go!');
    }
}
