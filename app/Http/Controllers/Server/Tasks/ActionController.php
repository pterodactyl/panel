<?php

namespace App\Http\Controllers\Server\Tasks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Services\Schedules\ProcessScheduleService;
use App\Contracts\Repository\ScheduleRepositoryInterface;

class ActionController extends Controller
{
    /**
     * @var \App\Services\Schedules\ProcessScheduleService
     */
    private $processScheduleService;

    /**
     * @var \App\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $repository;

    /**
     * ActionController constructor.
     *
     * @param \App\Services\Schedules\ProcessScheduleService        $processScheduleService
     * @param \App\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
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
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function trigger(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $this->authorize('toggle-schedule', $server);

        $this->processScheduleService->handle(
            $request->attributes->get('schedule')
        );

        return response('', 204);
    }
}
