<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class DashboardController extends Controller
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * DashboardController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function servers(Request $request)
    {
        $servers = $this->repository->setSearchTerm($request->input('query'))->filterUserAccessServers(
            $request->user(), User::FILTER_LEVEL_ALL
        );

        $data = [];
        foreach ($servers->items() as $server) {
            $cleaned = collect($server)->only([
                'uuidShort',
                'uuid',
                'name',
                'cpu',
                'memory',
            ]);

            $data[] = array_merge($cleaned->toArray(), [
                'allocation' => [
                    'ip' => $server->allocation->ip,
                    'port' => $server->allocation->port,
                ],
                'node_name' => $server->node->name,
            ]);
        }

        return response()->json($data);
    }
}
