<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Observers;

use App\Events;
use App\Models\Server;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server creating event.
     *
     * @param \App\Models\Server $server
     */
    public function creating(Server $server)
    {
        event(new Events\Server\Creating($server));
    }

    /**
     * Listen to the Server created event.
     *
     * @param \App\Models\Server $server
     */
    public function created(Server $server)
    {
        event(new Events\Server\Created($server));
    }

    /**
     * Listen to the Server deleting event.
     *
     * @param \App\Models\Server $server
     */
    public function deleting(Server $server)
    {
        event(new Events\Server\Deleting($server));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param \App\Models\Server $server
     */
    public function deleted(Server $server)
    {
        event(new Events\Server\Deleted($server));
    }

    /**
     * Listen to the Server saving event.
     *
     * @param \App\Models\Server $server
     */
    public function saving(Server $server)
    {
        event(new Events\Server\Saving($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param \App\Models\Server $server
     */
    public function saved(Server $server)
    {
        event(new Events\Server\Saved($server));
    }

    /**
     * Listen to the Server updating event.
     *
     * @param \App\Models\Server $server
     */
    public function updating(Server $server)
    {
        event(new Events\Server\Updating($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param \App\Models\Server $server
     */
    public function updated(Server $server)
    {
        event(new Events\Server\Updated($server));
    }
}
