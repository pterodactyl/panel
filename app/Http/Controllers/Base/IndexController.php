<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\ServerAccessHelperService;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class IndexController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerAccessHelperService
     */
    protected $access;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * IndexController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Services\Servers\ServerAccessHelperService            $access
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $repository
     */
    public function __construct(
        DaemonServerRepositoryInterface $daemonRepository,
        ServerAccessHelperService $access,
        ServerRepositoryInterface $repository
    ) {
        $this->access = $access;
        $this->daemonRepository = $daemonRepository;
        $this->repository = $repository;
    }

    /**
     * Returns listing of user's servers.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $servers = $this->repository->search($request->input('query'))->filterUserAccessServers(
            $request->user()->id, $request->user()->root_admin, 'all', ['user']
        );

        return view('base.index', ['servers' => $servers]);
    }

    /**
     * Returns status of the server in a JSON response used for populating active status list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function status(Request $request, $uuid)
    {
        $server = $this->access->handle($uuid, $request->user());

        if (! $server->installed) {
            return response()->json(['status' => 20]);
        } elseif ($server->suspended) {
            return response()->json(['status' => 30]);
        }

        $response = $this->daemonRepository->setNode($server->node_id)
            ->setAccessServer($server->uuid)
            ->setAccessToken($server->daemonSecret)
            ->details();

        return response()->json(json_decode($response->getBody()));
    }
}
