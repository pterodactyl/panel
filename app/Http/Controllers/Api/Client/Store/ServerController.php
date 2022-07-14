<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Exception;
use Throwable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Transformers\Api\Client\Store\EggTransformer;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Transformers\Api\Client\Store\NestTransformer;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\CreateServerRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreEggsRequest;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreNestsRequest;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class ServerController extends ClientApiController
{
    private NodeRepository $nodeRepository;
    private ServerCreationService $creationService;

    /**
     * ServerController constructor.
     */
    public function __construct(
        NodeRepository $nodeRepository,
        ServerCreationService $creationService
    ) {
        parent::__construct();
        $this->nodeRepository = $nodeRepository;
        $this->creationService = $creationService;
    }

    /**
     * Get all available eggs for server deployment.
     */
    public function eggs(GetStoreEggsRequest $request, Nest $nest): ?array
    {
        return $this->fractal->collection($nest->eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Get all available nests for server deployment.
     */
    public function nests(GetStoreNestsRequest $request): ?array
    {
        $nests = Nest::all();

        return $this->fractal->collection($nests)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Stores a new server on the Panel.
     *
     * @throws Throwable
     * @throws DisplayException
     * @throws ValidationException
     * @throws DataValidationException
     * @throws RecordNotFoundException
     * @throws NoViableNodeException
     * @throws NoViableAllocationException
     */
    public function store(CreateServerRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->verifyResources($request);

        $egg = Egg::find($request->input('egg'));
        $nest = Nest::find($request->input('nest'));

        $memory = $request->input('memory') * 1024;
        $disk = $request->input('disk') * 1024;

        $data = [
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'nest_id' => $nest->id,
            'allocation_id' => $this->getAllocation($request),
            'allocation_limit' => $request->input('ports'),
            'backup_limit' => $request->input('backups'),
            'database_limit' => $request->input('databases'),
            'environment' => [],
            'memory' => $memory,
            'disk' => $disk,
            'cpu' => $request->input('cpu'),
            'swap' => 0,
            'io' => 500,
            'image' => array_values($egg->docker_images)[0],
            'startup' => $egg->startup,
            'start_on_completion' => true,
            // Settings for the renewal system. Even if the renewal system is disabled,
            // mark this server as enabled - so that if the renewal system is enabled again,
            // it'll be part of the renewable servers.
            'renewable' => true,
            'renewal' => $this->settings->get('jexactyl::renewal:default'),
        ];

        foreach (DB::table('egg_variables')->where('egg_id', $egg->id)->get() as $var) {
            $key = "v1-{$egg->id}-{$var->env_variable}";
            $data['environment'][$var->env_variable] = $request->get($key, $var->default_value);
        }

        try {
            $server = $this->creationService->handle($data);
        } catch (DisplayException $exception) {
            throw new DisplayException('Unable to deploy server. Please contact an administrator.');
        }

        $user->update([
            'store_cpu' => $user->store_cpu - $request->input('cpu'),
            'store_memory' => $user->store_memory - $memory,
            'store_disk' => $user->store_disk - $disk,
            'store_slots' => $user->store_slots - 1,
            'store_ports' => $user->store_ports - $request->input('ports'),
            'store_backups' => $user->store_backups - $request->input('backups'),
            'store_databases' => $user->store_databases - $request->input('databases'),
        ]);

        return new JsonResponse([], ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Gets an allocation for server deployment.
     *
     * @throws DisplayException
     */
    protected function getAllocation(CreateServerRequest $request): int
    {
        $nodes = $this->nodeRepository->getNodesForServerCreation();
        $deployable_nodes = [];

        foreach ($nodes as $node) {
            $x = $this->nodeRepository->getNodeWithResourceUsage($node['id']);
            if ($x->getOriginal('sum_memory') <= $x->getOriginal('memory') - ($request->input('memory') * 1024)) {
                $deployable_nodes[$x->id] = $x->getOriginal('memory') - ($request->input('memory')) * 1024;
            }
        }

        if ($deployable_nodes < 1) {
            throw new DisplayException('Unable to find a node to deploy the server.');
        }

        $best_node = max($deployable_nodes);
        $node = array_search($best_node, $deployable_nodes);

        try {
            $allocation = DB::table('allocations')->where('node_id', $node)->where('server_id', null)->first()->id;
        } catch (Exception $e) {
            throw new DisplayException('Unable to find an allocation to deploy the server.');
        }

        return $allocation;
    }

    /**
     * Checks that the user has sufficient resources for server creation.
     *
     * @throws DisplayException
     */
    protected function verifyResources(CreateServerRequest $request)
    {
        $user = $request->user();

        if (
            $user->store_slots < 1 |
            $user->store_ports < 1 |
            $user->store_cpu < $request->input('cpu') |
            $user->store_disk < $request->input('disk') * 1024 |
            $user->store_memory < $request->input('memory') * 1024
        ) {
            throw new DisplayException('Unable to deploy instance: You do not have sufficient resources.');
        }
    }
}
