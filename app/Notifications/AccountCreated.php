<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user, public ?string $token = null)
    {
        $this->afterCommit();
    }

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
    public function toMail(): MailMessage
    {
        $message = (new MailMessage)
            ->greeting('Hello '.$this->user->name.'!')
            ->line('You are receiving this email because an account has been created for you on '.JsonValueGuard::string(config('app.name')).'.')
            ->line('Username: '.$this->user->username)
            ->line('Email: '.$this->user->email);

        if (($this->token) !== null) {
            return $message->action('Setup Your Account', url('/auth/password/reset/'.$this->token.'?email='.urlencode($this->user->email)));
        }

        return $message;
    }
}
