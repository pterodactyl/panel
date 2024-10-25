<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    public function __construct(private User $user)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject('Arion2000.xyz Gameservers Testnachricht')
            ->greeting('Hallo ' . $this->user->name . '!')
            ->line('Dies ist ein Test des Arion2000.xyz Mail-Systems. Es sieht alles gut aus!');
    }
}
