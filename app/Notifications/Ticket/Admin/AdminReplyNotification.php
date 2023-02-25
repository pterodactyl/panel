<?php

namespace App\Notifications\Ticket\Admin;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminReplyNotification extends Notification implements ShouldQueue
{
    //THIS IS BASICALLY NOT USED ANYMORE WITH INVOICENOTIFICATION IN PLACE

    use Queueable;

    private Ticket $ticket;

    private User $user;

    private $newmessage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, User $user, $newmessage)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->newmessage = $newmessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['mail', 'database'];

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('[Ticket ID: '.$this->ticket->ticket_id.'] '.$this->ticket->title)
            ->markdown('mail.ticket.admin.reply', ['ticket' => $this->ticket, 'user' => $this->user, 'newmessage' => $this->newmessage]);
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
            'title' => '[Ticket ID: '.$this->ticket->ticket_id.'] '.$this->ticket->title,
            'content' => "
                <p>Ticket With ID : {$this->ticket->ticket_id} has had a new reply posted by <strong>{$this->user->name}</strong></p>
                <br>
                <p><strong>Message:</strong></p>
                <p>{$this->newmessage}</p>
            ",
        ];
    }
}
