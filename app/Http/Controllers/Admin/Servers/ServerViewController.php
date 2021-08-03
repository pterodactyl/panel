<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use JavaScript;
use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\View\Factory;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Repositories\Eloquent\NestRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\MountRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;

class ServerViewController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository
     */
    private $databaseHostRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\MountRepository
     */
    protected $mountRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NestRepository
     */
    private $nestRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    private $environmentService;

    /**
     * ServerViewController constructor.
     */
    public function __construct(
        Factory $view,
        DatabaseHostRepository $databaseHostRepository,
        LocationRepository $locationRepository,
        MountRepository $mountRepository,
        NestRepository $nestRepository,
        NodeRepository $nodeRepository,
        ServerRepository $repository,
        EnvironmentService $environmentService
    ) {
        $this->view = $view;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->locationRepository = $locationRepository;
        $this->mountRepository = $mountRepository;
        $this->nestRepository = $nestRepository;
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
        $this->environmentService = $environmentService;
    }

    /**
     * Returns the index view for a server.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.index', compact('server'));
    }

    /**
     * Returns the server details page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function details(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.details', compact('server'));
    }

    /**
     * Returns a view of server build settings.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function build(Request $request, Server $server)
    {
        $allocations = $server->node->allocations->toBase();

        return $this->view->make('admin.servers.view.build', [
            'server' => $server,
            'assigned' => $allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
        ]);
    }

    /**
     * Returns the server startup management page.
     *
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function startup(Request $request, Server $server)
    {
        $nests = $this->nestRepository->getWithEggs();
        $variables = $this->environmentService->handle($server);

        $this->plainInject([
            'server' => $server,
            'server_variables' => $variables,
            'nests' => $nests->map(function (Nest $item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return $this->view->make('admin.servers.view.startup', compact('server', 'nests'));
    }

    /**
     * Returns all of the databases that exist for the server.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function database(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }

    /**
     * Returns all of the mounts that exist for the server.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function mounts(Request $request, Server $server)
    {
        $server->load('mounts');

        return $this->view->make('admin.servers.view.mounts', [
            'mounts' => $this->mountRepository->getMountListForServer($server),
            'server' => $server,
        ]);
    }

    /**
     * Returns the base server management page, or an exception if the server
     * is in a state that cannot be recovered from.
     *
     * @return \Illuminate\Contracts\View\View
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function manage(Request $request, Server $server)
    {
        if ($server->status === Server::STATUS_INSTALL_FAILED) {
            throw new DisplayException('This server is in a failed install state and cannot be recovered. Please delete and re-create the server.');
        }

        // Check if the panel doesn't have at least 2 nodes configured.
        $nodes = $this->nodeRepository->all();
        $canTransfer = false;
        if (count($nodes) >= 2) {
            $canTransfer = true;
        }

        Javascript::put([
            'nodeData' => $this->nodeRepository->getNodesForServerCreation(),
        ]);

        return $this->view->make('admin.servers.view.manage', [
            'server' => $server,
            'locations' => $this->locationRepository->all(),
            'canTransfer' => $canTransfer,
        ]);
    }

    /**
     * Returns the server deletion page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function delete(Request $request, Server $server)
    {
        return $this->view->make('admin.servers.view.delete', compact('server'));
    }
}
