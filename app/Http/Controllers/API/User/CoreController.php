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
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\User\OverviewTransformer;

class CoreController extends Controller
{
    /**
     * Controller to handle base user request for all of their servers.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('user.server-list', $request->apiKey());

        $servers = $request->user()->access('service', 'node', 'allocation', 'option')->get();

        return Fractal::collection($servers)
            ->transformWith(new OverviewTransformer)
            ->withResourceName('server')
            ->toArray();
    }
}
