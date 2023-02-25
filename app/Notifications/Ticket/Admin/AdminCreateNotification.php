<?php

namespace App\Notifications\Ticket\Admin;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCreateNotification extends Notification implements ShouldQueue
{
    //THIS IS BASICALLY NOT USED ANYMORE WITH INVOICENOTIFICATION IN PLACE

    use Queueable;

    private Ticket $ticket;

    private User $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, User $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
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
            ->markdown('mail.ticket.admin.create', ['ticket' => $this->ticket, 'user' => $this->user]);
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
            'content' => "Ticket With ID : {$this->ticket->ticket_id} has been opened by <strong>{$this->user->name}</strong>",
        ];
    }
}
