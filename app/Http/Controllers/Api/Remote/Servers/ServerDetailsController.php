<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class ServerDetailsController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Eggs\EggConfigurationService
     */
    private $eggConfigurationService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * ServerConfigurationController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Services\Servers\ServerConfigurationStructureService $configurationStructureService
     * @param \Pterodactyl\Services\Eggs\EggConfigurationService $eggConfigurationService
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $nodeRepository
     */
    public function __construct(
        ServerRepository $repository,
        ServerConfigurationStructureService $configurationStructureService,
        EggConfigurationService $eggConfigurationService,
        NodeRepository $nodeRepository
    ) {
        $this->eggConfigurationService = $eggConfigurationService;
        $this->repository = $repository;
        $this->configurationStructureService = $configurationStructureService;
    }

    /**
     * Returns details about the server that allows Wings to self-recover and ensure
     * that the state of the server matches the Panel at all times.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(Request $request, $uuid)
    {
        $server = $this->repository->getByUuid($uuid);

        return new JsonResponse([
            'settings' => $this->configurationStructureService->handle($server),
            'process_configuration' => $this->eggConfigurationService->handle($server),
        ]);
    }

    /**
     * Lists all servers with their configurations that are assigned to the requesting node.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection
     */
    public function list(Request $request)
    {
        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        // Avoid run-away N+1 SQL queries by pre-loading the relationships that are used
        // within each of the services called below.
        $servers = Server::query()->with('allocations', 'egg', 'mounts', 'variables', 'location')
            ->where('node_id', $node->id)
            ->paginate($request->input('per_page', 50));

        return new ServerConfigurationCollection($servers);
    }
}
