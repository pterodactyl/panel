<?php

namespace App\Http\Controllers\Server\Tasks;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Contracts\Extensions\HashidsInterface;
use App\Traits\Controllers\JavascriptInjection;
use App\Services\Schedules\ScheduleUpdateService;
use App\Services\Schedules\ScheduleCreationService;
use App\Contracts\Repository\ScheduleRepositoryInterface;
use App\Http\Requests\Server\ScheduleCreationFormRequest;

class TaskManagementController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Services\Schedules\ScheduleCreationService
     */
    protected $creationService;

    /**
     * @var \App\Contracts\Extensions\HashidsInterface
     */
    protected $hashids;

    /**
     * @var \App\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Schedules\ScheduleUpdateService
     */
    private $updateService;

    /**
     * TaskManagementController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param \App\Contracts\Extensions\HashidsInterface            $hashids
     * @param \App\Services\Schedules\ScheduleCreationService       $creationService
     * @param \App\Services\Schedules\ScheduleUpdateService         $updateService
     * @param \App\Contracts\Repository\ScheduleRepositoryInterface $repository
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
     * @param \App\Http\Requests\Server\ScheduleCreationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
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
                /* @var \App\Models\Task $task */
                return collect($task->toArray())->only('action', 'time_offset', 'payload')->all();
            }),
        ]);

        return view('server.schedules.view', ['schedule' => $schedule]);
    }

    /**
     * Update a specific parent task on the system.
     *
     * @param \App\Http\Requests\Server\ScheduleCreationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Schedule\Task\TaskIntervalTooLongException
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
