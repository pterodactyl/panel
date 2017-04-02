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
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Admin\ServerTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class ServerController extends Controller
{
    /**
     * Controller to handle returning all servers on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        $servers = Server::paginate(20);

        return Fractal::create()
            ->collection($servers)
            ->transformWith(new ServerTransformer)
            ->paginateWith(new IlluminatePaginatorAdapter($servers))
            ->withResourceName('server')
            ->toArray();
    }

    /**
     * Controller to handle returning information on a single server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function view(Request $request, $id)
    {
        $server = Server::findOrFail($id);

        $fractal = Fractal::create()->item($server);

        if ($request->input('include')) {
            $fractal->parseIncludes(collect(explode(',', $request->input('include')))->intersect([
                'allocations', 'subusers', 'user',
                'pack', 'service', 'option',
            ])->toArray());
        }

        return $fractal->transformWith(new ServerTransformer)
            ->withResourceName('server')
            ->toArray();
    }
}
