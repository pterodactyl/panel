<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Observers;

use Cache;
use Carbon;
use Pterodactyl\Events;
use Pterodactyl\Models\Server;
use Pterodactyl\Jobs\DeleteServer;
use Pterodactyl\Jobs\SuspendServer;
use Pterodactyl\Notifications\ServerCreated;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server creating event.
     *
     * @param  Server $server The server model.
     * @return void
     */
    public function creating(Server $server)
    {
        event(new Events\Server\Creating($server));
    }

    /**
     * Listen to the Server created event.
     *
     * @param  Server $server The server model.
     * @return void
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
     * @param  Server $server The server model.
     * @return void
     */
    public function deleting(Server $server)
    {
        event(new Events\Server\Deleting($server));

        $this->dispatch((new SuspendServer($server->id))->onQueue(config('pterodactyl.queues.high')));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param  Server $server The server model.
     * @return void
     */
    public function deleted(Server $server)
    {
        event(new Events\Server\Deleted($server));

        $this->dispatch(
            (new DeleteServer($server->id))
            ->delay(Carbon::now()->addMinutes(config('pterodactyl.tasks.delete_server')))
            ->onQueue(config('pterodactyl.queues.standard'))
        );
    }

    /**
     * Listen to the Server saving event.
     *
     * @param  Server $server The server model.
     * @return void
     */
    public function saving(Server $server)
    {
        event(new Events\Server\Saving($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param  Server $server The server model.
     * @return void
     */
    public function saved(Server $server)
    {
        event(new Events\Server\Saved($server));
    }

    /**
     * Listen to the Server updating event.
     *
     * @param  Server $server The server model.
     * @return void
     */
    public function updating(Server $server)
    {
        event(new Events\Server\Updating($server));
    }

    /**
     * Listen to the Server saved event.
     *
     * @param  Server $server The server model.
     * @return void
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

        event(new Events\Server\Updated($server));
    }
}
