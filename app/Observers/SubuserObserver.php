<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Notifications\AddedToServer;
use Pterodactyl\Notifications\RemovedFromServer;

class SubuserObserver
{
    /**
     * Listen to the Subuser creating event.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     */
    public function creating(Subuser $subuser)
    {
        event(new Events\Subuser\Creating($subuser));
    }

    /**
     * Listen to the Subuser created event.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     */
    public function created(Subuser $subuser)
    {
        event(new Events\Subuser\Created($subuser));

        $subuser->user->notify((new AddedToServer([
            'user' => $subuser->user->name_first,
            'name' => $subuser->server->name,
            'uuidShort' => $subuser->server->uuidShort,
        ])));
    }

    /**
     * Listen to the Subuser deleting event.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     */
    public function deleting(Subuser $subuser)
    {
        event(new Events\Subuser\Deleting($subuser));
    }

    /**
     * Listen to the Subuser deleted event.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     */
    public function deleted(Subuser $subuser)
    {
        event(new Events\Subuser\Deleted($subuser));

        $subuser->user->notify((new RemovedFromServer([
            'user' => $subuser->user->name_first,
            'name' => $subuser->server->name,
        ])));
    }
}
