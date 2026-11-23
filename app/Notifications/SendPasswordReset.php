<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;

class SendPasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    /** @param User $notifiable */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.password_reset.subject'))
            ->line(__('notifications.password_reset.intro'))
            ->action(__('notifications.password_reset.action'), url('/auth/password/reset/'.$this->token.'?email='.urlencode($notifiable->email)))
            ->line(__('notifications.password_reset.outro'));
    }
}
