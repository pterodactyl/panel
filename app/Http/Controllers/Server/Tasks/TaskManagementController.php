<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Server\Tasks;

use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Requests\Request;
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

        return view('server.tasks.index', [
            'schedules' => $this->repository->getServerSchedules($server->id),
            'actions' => [
                'command' => trans('server.tasks.actions.command'),
                'power' => trans('server.tasks.actions.power'),
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

        return view('server.tasks.new');
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
        $this->alert->success(trans('server.tasks.task_created'))->flash();

        return redirect()->route('server.tasks.view', [
            'server' => $server->uuidShort,
            'task' => $schedule->hashid,
        ]);
    }

    /**
     * Return a view to modify a schedule.
     *
     * @param \Pterodactyl\Http\Requests\Request $request
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

        return view('server.tasks.view', ['schedule' => $schedule]);
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
        $this->alert->success(trans('server.tasks.task_updated'))->flash();

        return redirect()->route('server.tasks.view', [
            'server' => $server->uuidShort,
            'task' => $schedule->hashid,
        ]);
    }

    /**
     * Delete a parent task from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(Request $request)
    {
        $server = $this->session->get('server_data.model');
        $schedule = $request->attributes->get('schedule');
        $this->authorize('delete-schedule', $server);

        $this->repository->delete($task->id);

        return response('', 204);
    }
}
