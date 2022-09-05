<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;

class VerifyEmail extends Notification
{
//    use Queueable;

    public User $user;
    public string $name;
    public string $token;

    public function __construct(User $user, string $name, string $token)
    {
        $this->user = $user;
        $this->name = $name;
        $this->token = $token;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $message = new MailMessage();
        $message->greeting('Hello '.$this->user->username.'! Welcome to '.$this->name.'.');
        $message->line('Please click the link below to verify your email address.');
        $message->action('Verify Email', url('/auth/verify/'.$this->token));
        $message->line('If you did not create this account please contact '.$this->name.'.');
        return $message;
    }
}
