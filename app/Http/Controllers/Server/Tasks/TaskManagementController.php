<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server\Tasks;

use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Schedules\ScheduleCreationService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;
use Pterodactyl\Http\Requests\Server\ScheduleCreationFormRequest;

class TaskManagementController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Schedules\ScheduleCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    protected $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * TaskManagementController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface            $hashids
     * @param \Illuminate\Contracts\Session\Session                         $session
     * @param \Pterodactyl\Services\Schedules\ScheduleCreationService       $creationService
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        HashidsInterface $hashids,
        Session $session,
        ScheduleCreationService $creationService,
        ScheduleRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->hashids = $hashids;
        $this->repository = $repository;
        $this->session = $session;
    }

    /**
     * Display the task page listing.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('list-schedules', $server);
        $this->injectJavascript();

        return view('server.schedules.index', [
            'schedules' => $this->repository->getServerSchedules($server->id),
            'actions' => [
                'command' => trans('server.schedule.actions.command'),
                'power' => trans('server.schedule.actions.power'),
            ],
        ]);
    }

    /**
     * Display the task creation page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('create-schedule', $server);
        $this->injectJavascript();

        return view('server.schedules.new');
    }

    /**
     * @param \Pterodactyl\Http\Requests\Server\ScheduleCreationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function store(ScheduleCreationFormRequest $request)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('create-schedule', $server);

        $schedule = $this->creationService->handle($server, $request->normalize(), $request->getTasks());
        $this->alert->success(trans('server.schedules.task_created'))->flash();

        return redirect()->route('server.schedules.view', [
            'server' => $server->uuidShort,
            'task' => $schedule->hashid,
        ]);
    }

    /**
     * Return a view to modify a schedule.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Request $request)
    {
        $server = $this->session->get('server_data.model');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('view-schedule', $server);

        $this->injectJavascript([
            'tasks' => $schedule->tasks->map(function ($schedule) {
                return collect($schedule->toArray())->only('action', 'time_offset', 'payload')->all();
            }),
        ]);

        return view('server.schedules.view', ['schedule' => $schedule]);
    }

    /**
     * Update a specific parent task on the system.
     *
     * @param \Pterodactyl\Http\Requests\Server\ScheduleCreationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(ScheduleCreationFormRequest $request)
    {
        $server = $this->session->get('server_data.model');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('edit-schedule', $server);

        //        $this->updateService->handle($task, $request->normalize(), $request->getChainedTasks());
        //        $this->alert->success(trans('server.schedules.task_updated'))->flash();

        return redirect()->route('server.schedules.view', [
            'server' => $server->uuidShort,
            'task' => $schedule->hashid,
        ]);
    }

    /**
     * Delete a parent task from the Panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(Request $request)
    {
        $server = $this->session->get('server_data.model');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('delete-schedule', $server);

        $this->repository->delete($schedule->id);

        return response('', 204);
    }
}
