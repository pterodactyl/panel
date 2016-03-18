<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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

use Alert;
use Log;
use Cron;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __constructor()
    {
        //
    }

    public function getIndex(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('list-tasks', $server);

        return view('server.tasks.index', [
            'server' => $server,
            'node' => Models\Node::findOrFail($server->node),
            'tasks' => Models\Task::where('server', $server->id)->get(),
            'actions' => [
                'command' => 'Send Command',
                'power' => 'Set Power Status'
            ]
        ]);
    }

    public function getNew(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('create-task', $server);

        return view('server.tasks.new', [
            'server' => $server,
            'node' => Models\Node::findOrFail($server->node)
        ]);
    }

    public function postNew(Request $request, $uuid)
    {
        dd($request->input());
    }

    public function getView(Request $request, $uuid, $id)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('view-task', $server);

        return view('server.tasks.view', [
            'server' => $server,
            'node' => Models\Node::findOrFail($server->node),
            'task' => Models\Task::where('id', $id)->where('server', $server->id)->firstOrFail()
        ]);
    }
}
