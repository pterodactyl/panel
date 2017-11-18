<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset token to send.
     *
     * @var object
     */
    public $user;

    /**
     * Create a new notification instance.
     *
     * @param array $user
     */
    public function __construct(array $user)
    {
        $this->user = (object) $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('You are recieving this email because an account has been created for you on Pterodactyl Panel.')
            ->line('Username: ' . $this->user->username)
            ->line('Email: ' . $notifiable->email);

        if (! is_null($this->user->token)) {
            return $message->action('Setup Your Account', url('/auth/password/reset/' . $this->user->token . '?email=' . $notifiable->email));
        }

        return $message;
    }
}
