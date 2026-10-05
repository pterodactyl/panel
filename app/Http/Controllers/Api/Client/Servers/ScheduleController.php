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
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\TriggerScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\ScheduleTransformer;
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
     *
     * @return ApiPayload
     */
    #[Endpoint('List server schedules', 'Returns all schedules configured for the server, including their tasks.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports tasks.', required: false, example: 'tasks')]
    #[ResponseFromTransformer(ScheduleTransformer::class, Schedule::class, description: 'Server schedules returned.', collection: true, resourceKey: 'server_schedule')]
    public function index(ViewScheduleRequest $request, Server $server): array
    {
        $schedules = $server->schedules->loadMissing('tasks');

        return Fractal::collection($schedules)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toResponseArray();
    }

    /**
     * Store a new schedule for a server.
     *
     *
     * @return ApiPayload
     *
     * @throws DisplayException
     */
    #[Endpoint('Create server schedule', 'Creates a schedule for the server.')]
    #[BodyParam('month', 'string', 'The month.', required: true, example: '*')]
    #[BodyParam('only_when_online', 'boolean', 'Whether the schedule should run only when the server is online.', required: false, example: true)]
    #[ResponseFromTransformer(ScheduleTransformer::class, Schedule::class, description: 'Schedule created.', resourceKey: 'server_schedule')]
    #[ScribeResponse(self::CRON_ERROR, status: 400, description: 'The cron expression is invalid.')]
    public function store(StoreScheduleRequest $request, CreatesSchedules $schedules, Server $server): array
    {
        $model = $schedules->create($server, $request->payload());

        Activity::event('server:schedule.create')
            ->subject($model)
            ->property('name', $model->name)
            ->log();

        return Fractal::item($model)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toResponseArray();
    }

    /**
     * Returns a specific schedule for the server.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server schedule', 'Returns one schedule configured for the server, including its tasks.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports tasks.', required: false, example: 'tasks')]
    #[ResponseFromTransformer(ScheduleTransformer::class, Schedule::class, description: 'Server schedule returned.', resourceKey: 'server_schedule')]
    public function view(ViewScheduleRequest $request, Server $server, Schedule $schedule): array
    {
        throw_if($schedule->server_id !== $server->id, NotFoundHttpException::class);

        $schedule->loadMissing('tasks');

        return Fractal::item($schedule)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toResponseArray();
    }

    /**
     * Updates a given schedule with the new data provided.
     *
     *
     * @return ApiPayload
     *
     * @throws DisplayException
     */
    #[Endpoint('Update server schedule', 'Updates a schedule for the server and recalculates its next run time.')]
    #[BodyParam('month', 'string', 'The month.', required: true, example: '*')]
    #[BodyParam('only_when_online', 'boolean', 'Whether the schedule should run only when the server is online.', required: false, example: true)]
    #[ResponseFromTransformer(ScheduleTransformer::class, Schedule::class, description: 'Schedule updated.', resourceKey: 'server_schedule')]
    #[ScribeResponse(self::CRON_ERROR, status: 400, description: 'The cron expression is invalid.')]
    public function update(UpdateScheduleRequest $request, UpdatesSchedules $schedules, Server $server, Schedule $schedule): array
    {
        $schedule = $schedules->update($schedule, $request->payload());

        Activity::event('server:schedule.update')
            ->subject($schedule)
            ->property(['name' => $schedule->name, 'active' => $schedule->is_active])
            ->log();

        return Fractal::item($schedule)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toResponseArray();
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

        Activity::event('server:schedule.execute')->subject($schedule)->property('name', $schedule->name)->log();

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

        Activity::event('server:schedule.delete')->subject($schedule)->property('name', $schedule->name)->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
