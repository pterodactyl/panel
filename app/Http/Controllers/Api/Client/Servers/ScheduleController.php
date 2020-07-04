<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ScheduleRepository;
use Pterodactyl\Transformers\Api\Client\ScheduleTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest;

class ScheduleController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ScheduleRepository
     */
    private $repository;

    /**
     * ScheduleController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ScheduleRepository $repository
     */
    public function __construct(ScheduleRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Returns all of the schedules belonging to a given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(ViewScheduleRequest $request, Server $server)
    {
        $schedules = $server->schedule;
        $schedules->loadMissing('tasks');

        return $this->fractal->collection($schedules)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Store a new schedule for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreScheduleRequest $request, Server $server)
    {
        /** @var \Pterodactyl\Models\Schedule $model */
        $model = $this->repository->create([
            'server_id' => $server->id,
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => (bool) $request->input('is_active'),
            'next_run_at' => $this->getNextRunAt($request),
        ]);

        return $this->fractal->item($model)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Returns a specific schedule for the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Schedule $schedule
     * @return array
     */
    public function view(ViewScheduleRequest $request, Server $server, Schedule $schedule)
    {
        if ($schedule->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        $schedule->loadMissing('tasks');

        return $this->fractal->item($schedule)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Updates a given schedule with the new data provided.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Schedule $schedule
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateScheduleRequest $request, Server $server, Schedule $schedule)
    {
        $this->repository->update($schedule->id, [
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => (bool) $request->input('is_active'),
            'next_run_at' => $this->getNextRunAt($request),
        ]);

        return $this->fractal->item($schedule->refresh())
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a schedule and it's associated tasks.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Schedule $schedule
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteScheduleRequest $request, Server $server, Schedule $schedule)
    {
        $this->repository->delete($schedule->id);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the next run timestamp based on the cron data provided.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Carbon\Carbon
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function getNextRunAt(Request $request): Carbon
    {
        try {
            return Utilities::getScheduleNextRunDate(
                $request->input('minute'),
                $request->input('hour'),
                $request->input('day_of_month'),
                $request->input('day_of_week')
            );
        } catch (Exception $exception) {
            throw new DisplayException(
                'The cron data provided does not evaluate to a valid expression.'
            );
        }
    }
}
