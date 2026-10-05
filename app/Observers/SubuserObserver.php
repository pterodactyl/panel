<?php

declare(strict_types=1);

namespace Pterodactyl\Observers;

use Pterodactyl\Events\Subuser\Created;
use Pterodactyl\Events\Subuser\Creating;
use Pterodactyl\Events\Subuser\Deleted;
use Pterodactyl\Events\Subuser\Deleting;
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
        event(new Creating($subuser));
    }

    /**
     * Listen to the Subuser created event.
     */
    public function created(Subuser $subuser): void
    {
        event(new Created($subuser));

        $subuser->user->notify(new AddedToServer([
            'user' => $subuser->user->name_first ?? $subuser->user->username,
            'name' => $subuser->server->name,
            'uuidShort' => $subuser->server->uuidShort,
        ]));
    }

    /**
     * Listen to the Subuser deleting event.
     */
    public function deleting(Subuser $subuser): void
    {
        event(new Deleting($subuser));
    }

    /**
     * Listen to the Subuser deleted event.
     */
    public function deleted(Subuser $subuser): void
    {
        event(new Deleted($subuser));

        $subuser->user->notify(new RemovedFromServer([
            'user' => $subuser->user->name_first ?? $subuser->user->username,
            'name' => $subuser->server->name,
        ]));
    }
}
