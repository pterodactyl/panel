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
use Pterodactyl\Models\Server;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server creating event.
     */
    public function creating(Server $server)
    {
        event(new Events\Server\Creating($server));
    }

    /**
     * Listen to the Server created event.
     */
    public function created(Server $server)
    {
        event(new Events\Server\Created($server));
    }

    /**
     * Listen to the Server deleting event.
     */
    public function deleting(Server $server)
    {
        event(new Events\Server\Deleting($server));
    }

    /**
     * Listen to the Server deleted event.
     */
    public function deleted(Server $server)
    {
        event(new Events\Server\Deleted($server));
    }

    /**
     * Listen to the Server saving event.
     */
    public function saving(Server $server)
    {
        event(new Events\Server\Saving($server));
    }

    /**
     * Listen to the Server saved event.
     */
    public function saved(Server $server)
    {
        event(new Events\Server\Saved($server));
    }

    /**
     * Listen to the Server updating event.
     */
    public function updating(Server $server)
    {
        event(new Events\Server\Updating($server));
    }

    /**
     * Listen to the Server saved event.
     */
    public function updated(Server $server)
    {
        event(new Events\Server\Updated($server));
    }
}
