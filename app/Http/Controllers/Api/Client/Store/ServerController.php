<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\CreateServerRequest;

class ServerController extends ClientApiController
{
    private NodeRepository $nodeRepository;
    private ServerCreationService $creationService;

    /**
     * ServerController constructor.
     */
    public function __construct(
        NodeRepository $nodeRepository,
        ServerCreationService $creationService,
    )
    {
        parent::__construct();
        $this->nodeRepository = $nodeRepository;
        $this->creationService = $creationService;
    }

    /**
     * Stores a new server on the Panel.
     * 
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     */
    public function store(CreateServerRequest $request): JsonResponse
    {
        $user = $request->user();
        $egg = DB::table('eggs')->where('id', $request->input('egg'))->first();

        $memory = $request->input('memory') * 1024;
        $disk = $request->input('disk') * 1024;

        $data = [
            'name' => $request->input('name'),
            'owner_id' => $request->user()->id,
            'egg_id' => $egg->id,
            'nest_id' => 1,
            'allocation_id' => $this->getAllocation(),
            'environment' => [],
            'memory' => $memory,
            'disk' => $disk,
            'cpu' => $request->input('cpu'),
            'swap' => 0,
            'io' => 500,
            'image' => $request->input('image'),
            'startup' => $egg->startup,
            'start_on_completion' => true,
        ];

        $this->verifyResources($user);

        try {
            $server = $this->creationService->handle($data);
        } catch (DisplayException $exception) {
            throw new DisplayException('Unable to deploy server. Please contact an administrator.');
        };

        $user->update([
            'store_slots' => $user->store_slots - 1,
            'store_cpu' => $user->store_cpu - $request->input('cpu'),
            'store_memory' => $user->store_memory - $memory,
            'store_disk' => $user->store_disk - $disk,
        ]);

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->respond(201);
    }

    /**
     * Gets an allocation for server deployment.
     * 
     * @throws \Pterodactyl\Exceptions\DisplayException
    */
    private function getAllocation(): int
    {
        $nodes = $this->nodeRepository->getNodesForServerCreation();
        $available_nodes = [];

        foreach ($nodes as $node) {
            $x = $this->nodeRepository->getNodeWithResourceUsage($node['id']);
            if ($x->getOriginal('sum_memory') <= $x->getOriginal('memory') - ($request->input('memory') * 1024)) {
                $available_nodes[] = $x->id;
            }
        }

        if ($available_nodes > 0) {
            $node = $available_nodes[0];
        } else {
            throw new DisplayException('Unable to find a node to deploy the server.');
        };

        try {
            $alloc = DB::table('allocations')
                ->where('node_id', $node)
                ->where('server_id', null)
                ->first();
        } catch (DisplayException $exception) {
            throw new DisplayException('Unable to find an allocation to deploy the server.');
        };

        return $alloc->id;
    }

    /**
     * Checks that the user has sufficient resources for server creation.
     * 
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    private function verifyResources(array $user)
    {
        if (
            $user->store_slots < 1 |
            $user->store_ports < 1 |
            $user->store_cpu < $request->input('cpu') |
            $user->store_disk < ($request->input('disk') * 1024) |
            $user->store_memory < ($request->input('memory') * 1024)
        ) {
            throw new DisplayException('Unable to deploy instance: You do not have sufficient resources.');
        };
    }
}
