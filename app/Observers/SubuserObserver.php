<?php

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Notifications\AddedToServer;
use Pterodactyl\Notifications\RemovedFromServer;

class SubuserObserver
{
    /**
     * Listen to the Subuser creating event.
     */
    public function creating(Subuser $subuser): void
    {
        event(new Events\Subuser\Creating($subuser));
    }

    /**
     * Listen to the Subuser created event.
     */
    public function created(Subuser $subuser): void
    {
        event(new Events\Subuser\Created($subuser));

        $subuser->user->notify(new AddedToServer([
            'user' => $subuser->user->username,
            'name' => $subuser->server->name,
            'uuidShort' => $subuser->server->uuidShort,
        ]));
    }

    /**
     * Listen to the Subuser deleting event.
     */
    public function deleting(Subuser $subuser): void
    {
        event(new Events\Subuser\Deleting($subuser));
    }

    /**
     * Listen to the Subuser deleted event.
     */
    public function deleted(Subuser $subuser): void
    {
        event(new Events\Subuser\Deleted($subuser));

        $subuser->user->notify(new RemovedFromServer([
            'user' => $subuser->user->username,
            'name' => $subuser->server->name,
        ]));
    }
}
