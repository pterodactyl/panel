<?php

namespace Pterodactyl\Listeners\Server;

use Pterodactyl\Events\Server\Installed;
use Pterodactyl\Notifications\ServerInstalled;

class SendInstalledEmail
{
    /**
     * Handle the event.
     *
     * @param  \Pterodactyl\Events\Server\Installed  $event
     */
    public function handle(Installed $event)
    {
        $event->server->user->notify(
            new ServerInstalled($event->server)
        );
    }
}
