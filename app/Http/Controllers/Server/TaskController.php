<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
    public function index(Request $request, $uuid)
    {
        $server = Server::byUuid($uuid)->load('tasks');
        $this->authorize('list-tasks', $server);
        $server->js();

        return view('server.schedules.index', [
            'server' => $server,
            'node' => $server->node,
            'tasks' => $server->tasks,
            'actions' => [
                'command' => trans('server.schedules.actions.command'),
                'power' => trans('server.schedules.actions.power'),
            ],
        ]);
    }

    /**
     * Display new task page.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $uuid)
    {
        $server = Server::byUuid($uuid);
        $this->authorize('create-task', $server);
        $server->js();

        return view('server.schedules.new', [
            'server' => $server,
            'node' => $server->node,
        ]);
    }

    /**
     * Handle creation of new task.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
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

            return redirect()->route('server.schedules', $uuid);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.schedules.new', $uuid)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to create this task.')->flash();
        }

        return redirect()->route('server.schedules.new', $uuid);
    }

    /**
     * Handle deletion of a task.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @param int                      $id
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
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @param int                      $id
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
