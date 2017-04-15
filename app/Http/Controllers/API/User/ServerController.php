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

namespace Pterodactyl\Http\Controllers\API\User;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Daemon\PowerRepository;
use Pterodactyl\Transformers\User\ServerTransformer;

class ServerController extends Controller
{
    /**
     * Controller to handle base request for individual server information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return array
     */
    public function index(Request $request, $uuid)
    {
        $this->authorize('user.server-view', $request->apiKey());

        $server = Server::byUuid($uuid);
        $fractal = Fractal::create()->item($server);

        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new ServerTransformer)
            ->withResourceName('server')
            ->toArray();
    }

    /**
     * Controller to handle request for server power toggle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\Http\Response
     */
    public function power(Request $request, $uuid)
    {
        $this->authorize('user.server-power', $request->apiKey());

        $server = Server::byUuid($uuid);
        $request->user()->can('power-' . $request->input('action'), $server);

        $repo = new PowerRepository($server, $request->user());
        $repo->do($request->input('action'));

        return response('', 204)->header('Content-Type', 'application/json');
    }

    /**
     * Controller to handle base request for individual server information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $uuid
     * @return \Illuminate\Http\Response
     */
    public function command(Request $request, $uuid)
    {
        $this->authorize('user.server-command', $request->apiKey());

        $server = Server::byUuid($uuid);
        $request->user()->can('send-command', $server);

        $repo = new CommandRepository($server, $request->user());
        $repo->send($request->input('command'));

        return response('', 204)->header('Content-Type', 'application/json');
    }
}
