<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Schedules\CreatesSchedules;
use Pterodactyl\Contracts\Schedules\DeletesSchedules;
use Pterodactyl\Contracts\Schedules\ProcessesSchedules;
use Pterodactyl\Contracts\Schedules\UpdatesSchedules;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\TriggerScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Client\ScheduleResource;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Schedules', 'Create, update, execute, delete, and manage scheduled server tasks.')]
class ScheduleController extends ClientApiController
{
    private const array CRON_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'The cron data provided does not evaluate to a valid expression.',
            ],
        ],
    ];

    /**
     * Returns all the schedules belonging to a given server.
     */
    #[Endpoint('List server schedules', 'Returns all schedules configured for the server, including their tasks.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports tasks.', required: false, example: 'tasks')]
    #[ResponseFromResource(ScheduleResource::class, Schedule::class, description: 'Server schedules returned.', collection: true, resourceKey: 'server_schedule')]
    public function index(ViewScheduleRequest $request, Server $server): ApiResourceCollection
    {
        $schedules = $server->schedules->loadMissing('tasks');

        return ScheduleResource::collection($schedules);
    }

    /**
     * Store a new schedule for a server.
     *
     * @throws DisplayException
     */
    #[Endpoint('Create server schedule', 'Creates a schedule for the server.')]
    #[BodyParam('month', 'string', 'The month.', required: true, example: '*')]
    #[BodyParam('only_when_online', 'boolean', 'Whether the schedule should run only when the server is online.', required: false, example: true)]
    #[ResponseFromResource(ScheduleResource::class, Schedule::class, description: 'Schedule created.', resourceKey: 'server_schedule')]
    #[ScribeResponse(self::CRON_ERROR, status: 400, description: 'The cron expression is invalid.')]
    public function store(StoreScheduleRequest $request, CreatesSchedules $schedules, Server $server): ScheduleResource
    {
        $model = $schedules->create($server, $request->actionInput());

        return ScheduleResource::make($model);
    }

    /**
     * Returns a specific schedule for the server.
     */
    #[Endpoint('Get server schedule', 'Returns one schedule configured for the server, including its tasks.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports tasks.', required: false, example: 'tasks')]
    #[ResponseFromResource(ScheduleResource::class, Schedule::class, description: 'Server schedule returned.', resourceKey: 'server_schedule')]
    public function view(ViewScheduleRequest $request, Server $server, Schedule $schedule): ScheduleResource
    {
        throw_if($schedule->server_id !== $server->id, NotFoundHttpException::class);

        $schedule->loadMissing('tasks');

        return ScheduleResource::make($schedule);
    }

    /**
     * Updates a given schedule with the new data provided.
     *
     * @throws DisplayException
     */
    #[Endpoint('Update server schedule', 'Updates a schedule for the server and recalculates its next run time.')]
    #[BodyParam('month', 'string', 'The month.', required: true, example: '*')]
    #[BodyParam('only_when_online', 'boolean', 'Whether the schedule should run only when the server is online.', required: false, example: true)]
    #[ResponseFromResource(ScheduleResource::class, Schedule::class, description: 'Schedule updated.', resourceKey: 'server_schedule')]
    #[ScribeResponse(self::CRON_ERROR, status: 400, description: 'The cron expression is invalid.')]
    public function update(UpdateScheduleRequest $request, UpdatesSchedules $schedules, Server $server, Schedule $schedule): ScheduleResource
    {
        $schedule = $schedules->update($schedule, $request->actionInput());

        return ScheduleResource::make($schedule);
    }

    /**
     * Executes a given schedule immediately rather than waiting on it's normally scheduled time
     * to pass. This does not care about the schedule state.
     *
     * @throws Throwable
     */
    #[Endpoint('Execute server schedule', 'Queues a schedule to run immediately regardless of its active state.')]
    #[ScribeResponse(status: 202, description: 'Schedule execution queued.')]
    public function execute(TriggerScheduleRequest $request, ProcessesSchedules $schedules, Server $server, Schedule $schedule): JsonResponse
    {
        $schedules->process($schedule, true);

        return new JsonResponse([], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * Deletes a schedule and it's associated tasks.
     */
    #[Endpoint('Delete server schedule', 'Deletes a schedule and its tasks from the server.')]
    #[ScribeResponse(status: 204, description: 'Schedule deleted.')]
    public function delete(DeleteScheduleRequest $request, DeletesSchedules $schedules, Server $server, Schedule $schedule): JsonResponse
    {
        $schedules->delete($schedule);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
