<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Task;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use Pterodactyl\Repositories\Eloquent\TaskRepository;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleTaskController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\TaskRepository
     */
    private $repository;

    /**
     * ScheduleTaskController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\TaskRepository $repository
     */
    public function __construct(TaskRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Determines if a user can delete the task for a given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\ClientApiRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Schedule $schedule
     * @param \Pterodactyl\Models\Task $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ClientApiRequest $request, Server $server, Schedule $schedule, Task $task)
    {
        if ($task->schedule_id !== $schedule->id || $schedule->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        if (! $request->user()->can(Permission::ACTION_SCHEDULE_UPDATE, $server)) {
            throw new HttpForbiddenException('You do not have permission to perform this action.');
        }

        $this->repository->delete($task->id);

        return JsonResponse::create(null, Response::HTTP_NO_CONTENT);
    }
}
