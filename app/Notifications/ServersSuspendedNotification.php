<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServersSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject(__('Your servers have been suspended!'))
                    ->greeting(__('Your servers have been suspended!'))
                    ->line(__('To automatically re-enable your server/s, you need to purchase more credits.'))
                    ->action(__('Purchase credits'), route('store.index'))
                    ->line(__('If you have any questions please let us know.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => __('Your servers have been suspended!'),
            'content' => '
                <h5>'.__('Your servers have been suspended!').'</h5>
                <p>'.__('To automatically re-enable your server/s, you need to purchase more credits.').'</p>
                <p>'.__('If you have any questions please let us know.').'</p>
                <p>'.__('Regards').',<br />'.config('app.name', 'Laravel').'</p>
            ',
        ];
    }
}
