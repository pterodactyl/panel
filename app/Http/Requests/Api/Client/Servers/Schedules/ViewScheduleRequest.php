<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

class ViewScheduleRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Determine if this resource can be viewed.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $server = $this->parameter('server', Server::class);
        $route = $this->route();
        throw_if($route === null, UnexpectedValueException::class, 'The schedule request does not have an active route.');

        $schedule = $route->parameter('schedule');

        // If the schedule does not belong to this server throw a 404 error. Also throw an
        // error if the task being requested does not belong to the associated schedule.
        if ($schedule instanceof Schedule) {
            $task = $route->parameter('task');
            throw_if($schedule->server_id !== $server->id || ($task instanceof Task && $task->schedule_id !== $schedule->id), NotFoundHttpException::class, 'The requested resource does not exist on the system.');
        }

        return true;
    }

    public function permission(): string
    {
        return Permissions::ScheduleRead->value;
    }
}
