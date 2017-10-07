<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\Admin;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Admin\ServiceTransformer;

class ServiceController extends Controller
{
    /**
     * Controller to handle returning all locations on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('service-list', $request->apiKey());

        return Fractal::create()
            ->collection(Nest::all())
            ->transformWith(new ServiceTransformer($request))
            ->withResourceName('service')
            ->toArray();
    }

    /**
     * Controller to handle returning information on a single server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return array
     */
    public function view(Request $request, $id)
    {
        $this->authorize('service-view', $request->apiKey());

        $service = Nest::findOrFail($id);
        $fractal = Fractal::create()->item($service);

        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new ServiceTransformer($request))
            ->withResourceName('service')
            ->toArray();
    }
}
