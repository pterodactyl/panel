<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class HookNotification extends Notification
{
    public function __construct(private string $subject, private string $messageBody, private ?string $greeting = null)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->subject)
            ->greeting($this->greeting ?? 'Hello ' . $notifiable->name . '!')
            ->line($this->messageBody);
    }
}
