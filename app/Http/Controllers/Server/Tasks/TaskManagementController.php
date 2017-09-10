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

use Illuminate\Contracts\Session\Session;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Tasks\TaskCreationService;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Http\Requests\Server\TaskCreationFormRequest;

class TaskManagementController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Pterodactyl\Services\Tasks\TaskCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    protected $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * TaskManagementController constructor.
     *
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface        $hashids
     * @param \Illuminate\Contracts\Session\Session                     $session
     * @param \Pterodactyl\Services\Tasks\TaskCreationService           $creationService
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface $repository
     */
    public function __construct(
        HashidsInterface $hashids,
        Session $session,
        TaskCreationService $creationService,
        TaskRepositoryInterface $repository
    ) {
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
        $this->authorize('list-tasks', $server);
        $this->injectJavascript();

        return view('server.tasks.index', [
            'tasks' => $this->repository->getParentTasksWithChainCount($server->id),
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
        $this->authorize('create-task', $server);
        $this->injectJavascript();

        return view('server.tasks.new');
    }

    /**
     * @param \Pterodactyl\Http\Requests\Server\TaskCreationFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function store(TaskCreationFormRequest $request)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('create-task', $server);

        $task = $this->creationService->handle($server, $request->normalize(), $request->getChainedTasks());

        return redirect()->route('server.tasks.view', [
            'server' => $server->uuidShort,
            'task' => $task->id,
        ]);
    }

    /**
     * Return a view to modify task settings.
     *
     * @param string $uuid
     * @param string $task
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($uuid, $task)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('edit-task', $server);
        $task = $this->repository->getTaskForServer($this->hashids->decodeFirst($task, 0), $server->id);

        $this->injectJavascript([
            'chained' => $task->chained->map(function ($chain) {
                return collect($chain->toArray())->only('action', 'chain_delay', 'data')->all();
            }),
        ]);

        return view('server.tasks.view', ['task' => $task]);
    }

    public function update(TaskCreationFormRequest $request, $uuid, $task)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('edit-task', $server);
        $task = $this->repository->getTaskForServer($this->hashids->decodeFirst($task, 0), $server->id);
    }
}
