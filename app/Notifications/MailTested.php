<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail()
    {
        return (new MailMessage())
            ->subject('翼龙面板邮件测试信息')
            ->greeting('你好 ' . $this->user->name . '!')
            ->line('这里是翼龙面板邮件系统，如果你收到这份邮件，说明邮件系统可以正常运行了!');
    }
}
