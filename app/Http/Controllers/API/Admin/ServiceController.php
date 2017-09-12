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

namespace Pterodactyl\Http\Controllers\API\Admin;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Service;
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
            ->collection(Service::all())
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

        $service = Service::findOrFail($id);
        $fractal = Fractal::create()->item($service);

        if ($request->input('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->transformWith(new ServiceTransformer($request))
            ->withResourceName('service')
            ->toArray();
    }
}
