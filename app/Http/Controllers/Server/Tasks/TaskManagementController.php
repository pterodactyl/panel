<?php

namespace Pterodactyl\Http\Controllers\Server\Tasks;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Schedules\ScheduleUpdateService;
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
     * @var \Pterodactyl\Services\Schedules\ScheduleUpdateService
     */
    private $updateService;

    /**
     * TaskManagementController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface            $hashids
     * @param \Pterodactyl\Services\Schedules\ScheduleCreationService       $creationService
     * @param \Pterodactyl\Services\Schedules\ScheduleUpdateService         $updateService
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        HashidsInterface $hashids,
        ScheduleCreationService $creationService,
        ScheduleUpdateService $updateService,
        ScheduleRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->hashids = $hashids;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Display the task page listing.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('list-schedules', $server);
        $this->setRequest($request)->injectJavascript();

        return view('server.schedules.index', [
            'schedules' => $this->repository->findServerSchedules($server->id),
            'actions' => [
                'command' => trans('server.schedule.actions.command'),
                'power' => trans('server.schedule.actions.power'),
            ],
        ]);
    }

    /**
     * Display the task creation page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('create-schedule', $server);
        $this->setRequest($request)->injectJavascript();

        return view('server.schedules.new');
    }

    /**
     * Handle request to store a new schedule and tasks in the database.
     *
     * @param \Pterodactyl\Http\Requests\Server\ScheduleCreationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function store(ScheduleCreationFormRequest $request): RedirectResponse
    {
        $server = $request->attributes->get('server');

        $schedule = $this->creationService->handle($server, $request->normalize(), $request->getTasks());
        $this->alert->success(trans('server.schedule.schedule_created'))->flash();

        return redirect()->route('server.schedules.view', [
            'server' => $server->uuidShort,
            'schedule' => $schedule->hashid,
        ]);
    }

    /**
     * Return a view to modify a schedule.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Request $request): View
    {
        $server = $request->attributes->get('server');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('view-schedule', $server);

        $this->setRequest($request)->injectJavascript([
            'tasks' => $schedule->getRelation('tasks')->map(function ($task) {
                /* @var \Pterodactyl\Models\Task $task */
                return collect($task->toArray())->only('action', 'time_offset', 'payload')->all();
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
     */
    public function update(ScheduleCreationFormRequest $request): RedirectResponse
    {
        $server = $request->attributes->get('server');
        $schedule = $request->attributes->get('schedule');

        $this->updateService->handle($schedule, $request->normalize(), $request->getTasks());
        $this->alert->success(trans('server.schedule.schedule_updated'))->flash();

        return redirect()->route('server.schedules.view', [
            'server' => $server->uuidShort,
            'schedule' => $schedule->hashid,
        ]);
    }

    /**
     * Delete a parent task from the Panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('delete-schedule', $server);

        $this->repository->delete($schedule->id);

        return response('', 204);
    }
}
