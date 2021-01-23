<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The authentication token to be used for the user to set their
     * password for the first time.
     *
     * @var string|null
     */
    public $token;

    /**
     * The user model for the created user.
     *
     * @var \Pterodactyl\Models\User
     */
    public $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $token = null)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage())
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('You are receiving this email because an account has been created for you on ' . config('app.name') . '.')
            ->line('Username: ' . $this->user->username)
            ->line('Email: ' . $this->user->email);

        if (!is_null($this->token)) {
            return $message->action('Setup Your Account', url('/auth/password/reset/' . $this->token . '?email=' . urlencode($this->user->email)));
        }

        return $message;
    }
}
