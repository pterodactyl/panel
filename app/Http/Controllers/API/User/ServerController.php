<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\User;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\User\ServerTransformer;
use Pterodactyl\Repositories\old_Daemon\PowerRepository;
use Pterodactyl\Repositories\old_Daemon\CommandRepository;

class ServerController extends Controller
{
    /**
     * Controller to handle base request for individual server information.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
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
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
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
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
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
