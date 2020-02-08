<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\ScheduleTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class ScheduleController extends ClientApiController
{
    /**
     * Returns all of the schedules belonging to a given server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(Request $request, Server $server)
    {
        $schedules = $server->schedule;
        $schedules->loadMissing('tasks');

        return $this->fractal->collection($schedules)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }
}
