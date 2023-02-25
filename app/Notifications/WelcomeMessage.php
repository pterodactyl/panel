<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new notification instance.
     *
     * @param  User  $user
     */
    public function __construct(User $user)
    {
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
        return ['database'];
    }

    public function AdditionalLines()
    {
        $AdditionalLine = '';
        if (config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL') != 0) {
            $AdditionalLine .= __('Verifying your e-mail address will grant you ').config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL').' '.__('additional').' '.config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME').'. <br />';
        }
        if (config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL') != 0) {
            $AdditionalLine .= __('Verifying your e-mail will also increase your Server Limit by ').config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL').'. <br />';
        }
        $AdditionalLine .= '<br />';
        if (config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD') != 0) {
            $AdditionalLine .= __('You can also verify your discord account to get another ').config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD').' '.config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME').'. <br />';
        }
        if (config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD') != 0) {
            $AdditionalLine .= __('Verifying your Discord account will also increase your Server Limit by ').config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD').'. <br />';
        }

        return $AdditionalLine;
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
            'title' => __('Getting started!'),
            'content' => '
               <p> '.__('Hello')." <strong>{$this->user->name}</strong>, ".__('Welcome to our dashboard').'!</p>
                <h5>'.__('Verification').'</h5>
                <p>'.__('You can verify your e-mail address and link/verify your Discord account.').'</p>
                <p>
                  '.$this->AdditionalLines().'
                </p>
                <h5>'.__('Information').'</h5>
                <p>'.__('This dashboard can be used to create and delete servers').'.<br /> '.__('These servers can be used and managed on our pterodactyl panel').'.<br /> '.__('If you have any questions, please join our Discord server and #create-a-ticket').'.</p>
                <p>'.__('We hope you can enjoy this hosting experience and if you have any suggestions please let us know').'!</p>
                <p>'.__('Regards').',<br />'.config('app.name', 'Laravel').'</p>
            ',
        ];
    }
}
