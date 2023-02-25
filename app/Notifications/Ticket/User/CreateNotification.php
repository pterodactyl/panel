<?php

namespace App\Notifications\Ticket\User;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateNotification extends Notification implements ShouldQueue
{
    //THIS IS BASICALLY NOT USED ANYMORE WITH INVOICENOTIFICATION IN PLACE

    use Queueable;

    private Ticket $ticket;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
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
            ->markdown('mail.ticket.user.create', ['ticket' => $this->ticket]);
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
            'content' => "Your Ticket has been Created With ID : {$this->ticket->ticket_id}",
        ];
    }
}
