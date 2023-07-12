<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Mounts;

use Illuminate\Http\Response;
use Pterodactyl\Models\Mount;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Transformers\Api\Application\MountTransformer;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountsRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\MountEggsRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\MountNodesRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\StoreMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\DeleteMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\UpdateMountRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class MountController extends ApplicationApiController
{
    /**
     * MountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an array of all mount.
     */
    public function index(GetMountsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '10');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $mounts = QueryBuilder::for(Mount::query())
            ->allowedFilters(['id', 'name', 'source', 'target'])
            ->allowedSorts(['id', 'name', 'source', 'target'])
            ->paginate($perPage);

        return $this->fractal->collection($mounts)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Returns a single mount.
     */
    public function view(GetMountRequest $request, Mount $mount): array
    {
        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Creates a new mount.
     */
    public function store(StoreMountRequest $request): JsonResponse
    {
        $mount = Mount::query()->create($request->validated());

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a mount.
     */
    public function update(UpdateMountRequest $request, Mount $mount): array
    {
        $mount->update($request->validated());

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a mount.
     *
     * @throws \Exception
     */
    public function delete(DeleteMountRequest $request, Mount $mount): Response
    {
        $mount->delete();

        return $this->returnNoContent();
    }

    /**
     * Attaches eggs to a mount.
     */
    public function addEggs(MountEggsRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $eggs = $data['eggs'] ?? [];
        if (count($eggs) > 0) {
            $mount->eggs()->syncWithoutDetaching($eggs);
        }

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Attaches nodes to a mount.
     */
    public function addNodes(MountNodesRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $mount->nodes()->syncWithoutDetaching($nodes);
        }

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Detaches eggs from a mount.
     */
    public function deleteEggs(MountEggsRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $eggs = $data['eggs'] ?? [];
        if (count($eggs) > 0) {
            $mount->eggs()->detach($eggs);
        }

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }

    /**
     * Detaches nodes from a mount.
     */
    public function deleteNodes(MountNodesRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $mount->nodes()->detach($nodes);
        }

        return $this->fractal->item($mount)
            ->transformWith(MountTransformer::class)
            ->toArray();
    }
}
