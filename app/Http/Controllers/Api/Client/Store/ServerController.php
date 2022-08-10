<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Store;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Nest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Store\StoreCreationService;
use Pterodactyl\Transformers\Api\Client\Store\EggTransformer;
use Pterodactyl\Transformers\Api\Client\Store\NestTransformer;
use Pterodactyl\Transformers\Api\Client\Store\NodeTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Store\CreateServerRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreEggsRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreNestsRequest;
use Pterodactyl\Http\Requests\Api\Client\Store\GetStoreNodesRequest;

class ServerController extends ClientApiController
{
    private StoreCreationService $creationService;

    /**
     * ServerController constructor.
     */
    public function __construct(StoreCreationService $creationService)
    {
        parent::__construct();

        $this->creationService = $creationService;
    }

    public function nodes(GetStoreNodesRequest $request): array
    {
        $nodes = Node::where('deployable', true)->get();

        return $this->fractal->collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }

    /**
     * Get all available nests for server deployment.
     */
    public function nests(GetStoreNestsRequest $request): array
    {
        return $this->fractal->collection(Nest::all())
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Get all available eggs for server deployment.
     */
    public function eggs(GetStoreEggsRequest $request): array
    {
        $id = $request->input('id') ?? Nest::first()->id;

        return $this->fractal->collection(Nest::query()->where('id', $id)->first()->eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Stores a new server on the Panel.
     *
     * @throws DisplayException
     * @throws NoViableNodeException
     */
    public function store(CreateServerRequest $request): JsonResponse
    {
        $user = $request->user();
        $disk = $request->input('disk') * 1024;
        $memory = $request->input('memory') * 1024;

        $this->creationService->handle($request);

        $user->update([
            'store_cpu' => $user->store_cpu - $request->input('cpu'),
            'store_memory' => $user->store_memory - $memory,
            'store_disk' => $user->store_disk - $disk,
            'store_slots' => $user->store_slots - 1,
            'store_ports' => $user->store_ports - $request->input('ports'),
            'store_backups' => $user->store_backups - $request->input('backups'),
            'store_databases' => $user->store_databases - $request->input('databases'),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

}
