<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Pterodactyl\Models\User;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public User $user;
    public string $token;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $message = new MailMessage();
        $message->greeting('Hello'.$this->user->name_first.'! Welcome to '.config('app.name').'.');
        $message->line('Please click the link below to verify your email address.');
        $message->action('Verify Email', url('/auth/verify'.$this->token));
        $message->line('If you did not create this account please contact '.config('app.name').'.');
        return $message;
    }
}
