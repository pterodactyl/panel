<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Schedules\CreatesScheduleTasks;
use Pterodactyl\Contracts\Schedules\DeletesScheduleTasks;
use Pterodactyl\Contracts\Schedules\UpdatesScheduleTasks;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Exceptions\Service\ServiceLimitExceededException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteTaskRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreTaskRequest;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Task;
use Pterodactyl\Transformers\Api\Client\TaskTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Schedules', 'Create, update, execute, delete, and manage scheduled server tasks.')]
class ScheduleTaskController extends ClientApiController
{
    private const array LIMIT_ERROR = [
        'errors' => [
            [
                'code' => 'ServiceLimitExceededException',
                'status' => '400',
                'detail' => 'Schedules may not have more than 10 tasks associated with them. Creating this task would put this schedule over the limit.',
            ],
        ],
    ];

    /**
     * Create a new task for a given schedule and store it in the database.
     *
     *
     * @return ApiPayload
     *
     * @throws ServiceLimitExceededException
     */
    #[Endpoint('Create schedule task', 'Creates a task in a server schedule.')]
    #[ResponseFromTransformer(TaskTransformer::class, Task::class, description: 'Schedule task created.', resourceKey: 'schedule_task')]
    #[ScribeResponse(self::LIMIT_ERROR, status: 400, description: 'The schedule has reached its configured task limit.')]
    public function store(StoreTaskRequest $request, CreatesScheduleTasks $tasks, Server $server, Schedule $schedule): array
    {
        $this->assertBackupTaskAllowed($request, $server);

        $task = $tasks->create($schedule, $request->payload());

        Activity::event('server:task.create')
            ->subject($schedule, $task)
            ->property(['name' => $schedule->name, 'action' => $task->action, 'payload' => $task->payload])
            ->log();

        return Fractal::item($task)
            ->transformWith($this->getTransformer(TaskTransformer::class))
            ->toResponseArray();
    }

    /**
     * Updates a given task for a server.
     *
     *
     * @return ApiPayload
     */
    #[Endpoint('Update schedule task', 'Updates a task in a server schedule, including its sequence position.')]
    #[ResponseFromTransformer(TaskTransformer::class, Task::class, description: 'Schedule task updated.', resourceKey: 'schedule_task')]
    public function update(StoreTaskRequest $request, UpdatesScheduleTasks $tasks, Server $server, Schedule $schedule, Task $task): array
    {
        $this->assertBackupTaskAllowed($request, $server);

        $task = $tasks->update($schedule, $task, $request->payload());

        Activity::event('server:task.update')
            ->subject($schedule, $task)
            ->property(['name' => $schedule->name, 'action' => $task->action, 'payload' => $task->payload])
            ->log();

        return Fractal::item($task)
            ->transformWith($this->getTransformer(TaskTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete a given task for a schedule. If there are subsequent tasks stored in the database
     * for this schedule their sequence IDs are decremented properly.
     */
    #[Endpoint('Delete schedule task', 'Deletes a task from a server schedule and compacts subsequent sequence IDs.')]
    #[ScribeResponse(status: 204, description: 'Schedule task deleted.')]
    public function delete(DeleteTaskRequest $request, DeletesScheduleTasks $tasks, Server $server, Schedule $schedule, Task $task): JsonResponse
    {
        $tasks->delete($schedule, $task);

        Activity::event('server:task.delete')->subject($schedule, $task)->property('name', $schedule->name)->log();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * A backup task is refused while the server cannot hold any backups.
     *
     * @throws HttpForbiddenException
     */
    private function assertBackupTaskAllowed(StoreTaskRequest $request, Server $server): void
    {
        throw_if($server->backup_limit === 0 && $request->payload()['action'] === 'backup', HttpForbiddenException::class, "A backup task cannot be created when the server's backup limit is set to 0.");
    }
}
