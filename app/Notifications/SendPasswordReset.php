<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendPasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $token)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Passwort zurücksetzen')
            ->line('Du erhältst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für deinen Account erhalten haben.')
            ->action('Passwort zurücksetzen', url('/auth/password/reset/' . $this->token . '?email=' . urlencode($notifiable->email)))
            ->line('Wenn du keine Zurücksetzungs des Passworts beantragt hast, sind keine weiteren Schritte erforderlich.');
    }
}
