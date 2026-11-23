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
            ->subject(__('notifications.mail_tested.subject'))
            ->greeting(__('notifications.mail_tested.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.mail_tested.intro'));
    }
}
