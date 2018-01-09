<?php

namespace Pterodactyl\Http\Controllers\Server\Tasks;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ActionController extends Controller
{
    private $processScheduleService;
    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $repository;

    public function __construct(ProcessScheduleService $processScheduleService, ScheduleRepositoryInterface $repository)
    {
        $this->processScheduleService = $processScheduleService;
        $this->repository = $repository;
    }

    /**
     * Toggle a task to be active or inactive for a given server.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function toggle(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('toggle-schedule', $server);

        $this->repository->update($schedule->id, [
            'is_active' => ! $schedule->is_active,
        ]);

        return response('', 204);
    }

    /**
     * Trigger a schedule to run now.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function trigger(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $this->authorize('toggle-schedule', $server);

        $this->processScheduleService->setRunTimeOverride(Carbon::now())->handle(
            $request->attributes->get('schedule')
        );

        return response('', 204);
    }
}
