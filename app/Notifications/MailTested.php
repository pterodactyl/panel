<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;

class MailTested extends Notification
{
    public function __construct(private readonly User $user) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Pterodactyl Test Message')
            ->greeting('Hello '.$this->user->name.'!')
            ->line("This is a test of the Pterodactyl mail system. You're good to go!");
    }
}
