<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DynamicNotification extends Notification
{
    use Queueable;

    /**
     * @var array
     */
    private $via;

    /**
     * @var array
     */
    private $database;

    /**
     * @var MailMessage
     */
    private $mail;

    /**
     * Create a new notification instance.
     *
     * @param  array  $via
     * @param  array  $database
     * @param  MailMessage  $mail
     */
    public function __construct($via, $database, $mail)
    {
        $this->via = $via;
        $this->database = $database;
        $this->mail = $mail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
    {
        return $this->via;
    }

    public function toMail()
    {
        return $this->mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray()
    {
        return $this->database;
    }
}
