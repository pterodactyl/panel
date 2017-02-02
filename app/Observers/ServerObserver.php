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

use Carbon;
use Pterodactyl\Events;
use Pterodactyl\Models;
use Pterodactyl\Models\Server;
use Pterodactyl\Jobs\DeleteServer;
use Pterodactyl\Jobs\SuspendServer;
use Pterodactyl\Notifications\ServerCreated;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server deleted event.
     *
     * @param  Server $server [description]
     * @return [type]         [description]
     */
    public function creating(Server $server)
    {
        event(new Events\Server\Creating($server));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param  Server $server [description]
     * @return [type]         [description]
     */
    public function created(Server $server)
    {
        event(new Events\Server\Created($server));

        // Queue Notification Email
        $user = Models\User::findOrFail($server->owner_id);
        $node = Models\Node::select('name')->where('id', $server->node_id)->first();
        $service = Models\Service::select('services.name', 'service_options.name as optionName')
            ->join('service_options', 'service_options.parent_service', '=', 'services.id')
            ->where('services.id', $server->service_id)
            ->where('service_options.id', $server->option_id)
            ->first();

        $user->notify((new ServerCreated([
            'name' => $server->name,
            'memory' => $server->memory,
            'node' => $node->name,
            'service' => $service->name,
            'option' => $service->optionName,
            'uuidShort' => $server->uuidShort,
        ])));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param  Server $server [description]
     * @return [type]         [description]
     */
    public function deleting(Server $server)
    {
        event(new Events\Server\Deleting($server));

        $this->dispatch((new SuspendServer($server->id))->onQueue(env('QUEUE_HIGH', 'high')));
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param  Server $server [description]
     * @return [type]         [description]
     */
    public function deleted(Server $server)
    {
        event(new Events\Server\Deleted($server));

        $this->dispatch(
            (new DeleteServer($server->id))
            ->delay(Carbon::now()->addMinutes(env('APP_DELETE_MINUTES', 10)))
            ->onQueue(env('QUEUE_STANDARD', 'standard'))
        );
    }
}
