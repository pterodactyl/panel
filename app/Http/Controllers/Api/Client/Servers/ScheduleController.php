<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Transformers\Api\Client\ScheduleTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * Returns a specific schedule for the server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Schedule $schedule
     * @return array
     */
    public function view(Request $request, Server $server, Schedule $schedule)
    {
        if ($schedule->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        $schedule->loadMissing('tasks');

        return $this->fractal->item($schedule)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }
}
