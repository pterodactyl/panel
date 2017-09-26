<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Observers;

use Cache;
use Pterodactyl\Events;
use Pterodactyl\Models\Server;
use Pterodactyl\Notifications\ServerCreated;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server creating event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function creating(Server $server)
    {
        event(new Events\Server\Creating($server));
    }

    /**
     * Listen to the Server created event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function created(Server $server)
    {
        event(new Events\Server\Created($server));

        // Queue Notification Email
        $server->user->notify((new ServerCreated([
            'name' => $server->name,
            'memory' => $server->memory,
            'node' => $server->node->name,
            'service' => $server->service->name,
            'option' => $server->option->name,
            'uuidShort' => $server->uuidShort,
        ])));
    }

    /**
     * Listen to the Server deleting event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function deleting(Server $server)
    {
        event(new Events\Server\Deleting($server));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function deleted(Server $server)
    {
        event(new Events\Server\Deleted($server));
    }

    /**
     * Listen to the Server saving event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function saving(Server $server)
    {
        event(new Events\Server\Saving($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function saved(Server $server)
    {
        event(new Events\Server\Saved($server));
    }

    /**
     * Listen to the Server updating event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function updating(Server $server)
    {
        event(new Events\Server\Updating($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param \Pterodactyl\Models\Server $server
     */
    public function updated(Server $server)
    {
        /*
         * The cached byUuid model calls are tagged with Model:Server:byUuid:<uuid>
         * so that they can be accessed regardless of if there is an Auth::user()
         * defined or not.
         *
         * We can also delete all cached byUuid items using the Model:Server tag.
         */
        Cache::tags('Model:Server:byUuid:' . $server->uuid)->flush();
        Cache::tags('Model:Server:byUuid:' . $server->uuidShort)->flush();
        Cache::tags('Downloads:Server:' . $server->uuid)->flush();

        event(new Events\Server\Updated($server));
    }
}
