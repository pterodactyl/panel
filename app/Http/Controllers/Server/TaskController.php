<?php
/**
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

namespace Pterodactyl\Http\Controllers\Server;

use Log;
use Alert;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\TaskRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class TaskController extends Controller
{
    /**
     * Display task index page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\View\View
     */
    public function index(Request $request, $uuid)
    {
        $server = Server::byUuid($uuid)->load('tasks');
        $this->authorize('list-tasks', $server);
        $server->js();

        return view('server.tasks.index', [
            'server' => $server,
            'node' => $server->node,
            'tasks' => $server->tasks,
            'actions' => [
                'command' => trans('server.tasks.actions.command'),
                'power' => trans('server.tasks.actions.power'),
            ],
        ]);
    }

    /**
     * Display new task page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $uuid)
    {
        $server = Server::byUuid($uuid);
        $this->authorize('create-task', $server);
        $server->js();

        return view('server.tasks.new', [
            'server' => $server,
            'node' => $server->node,
        ]);
    }

    /**
     * Handle creation of new task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $uuid)
    {
        $server = Server::byUuid($uuid);
        $this->authorize('create-task', $server);

        $repo = new TaskRepository;
        try {
            $repo->create($server->id, $request->user()->id, $request->except([
                '_token',
            ]));

            return redirect()->route('server.tasks', $uuid);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.tasks.new', $uuid)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to create this task.')->flash();
        }

        return redirect()->route('server.tasks.new', $uuid);
    }

    /**
     * Handle deletion of a task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @param  int                       $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $uuid, $id)
    {
        $server = Server::byUuid($uuid)->load('tasks');
        $this->authorize('delete-task', $server);

        $task = $server->tasks->where('id', $id)->first();
        if (! $task) {
            return response()->json([
                'error' => 'No task by that ID was found associated with this server.',
            ], 404);
        }

        $repo = new TaskRepository;
        try {
            $repo->delete($id);

            return response()->json([], 204);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'A server error occured while attempting to delete this task.',
            ], 503);
        }
    }

    /**
     * Toggle the status of a task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @param  int                       $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request, $uuid, $id)
    {
        $server = Server::byUuid($uuid)->load('tasks');
        $this->authorize('toggle-task', $server);

        $task = $server->tasks->where('id', $id)->first();
        if (! $task) {
            return response()->json([
                'error' => 'No task by that ID was found associated with this server.',
            ], 404);
        }

        $repo = new TaskRepository;
        try {
            $resp = $repo->toggle($id);

            return response()->json([
                'status' => $resp,
            ]);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'A server error occured while attempting to toggle this task.',
            ], 503);
        }
    }
}
