<?php

declare(strict_types=1);

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;

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
            ->subject(__('notifications.account_created.subject'))
            ->greeting(__('notifications.account_created.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.account_created.intro', ['app' => config()->string('app.name')]))
            ->line(__('notifications.account_created.username', ['username' => $this->user->username]))
            ->line(__('notifications.account_created.email', ['email' => $this->user->email]));

        if (($this->token) !== null) {
            return $message->action(__('notifications.account_created.action'), url('/auth/password/reset/'.$this->token.'?email='.urlencode($this->user->email)));
        }

        return $message;
    }
}
