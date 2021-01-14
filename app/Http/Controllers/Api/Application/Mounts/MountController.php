<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Mounts;

use Pterodactyl\Models\Mount;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Transformers\Api\Application\MountTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountsRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\StoreMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\UpdateMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\DeleteMountRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddEggsRequest;
use Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddNodesRequest;
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
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountsRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetMountsRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } elseif ($perPage > 100) {
            throw new BadRequestHttpException('"per_page" query parameter must be below 100.');
        }

        $mounts = QueryBuilder::for(Mount::query())
            ->allowedFilters(['name', 'host'])
            ->allowedSorts(['id', 'name', 'host'])
            ->paginate($perPage);

        return $this->fractal->collection($mounts)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single mount.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\GetMountRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetMountRequest $request, Mount $mount): array
    {
        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new mount.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\StoreMountRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreMountRequest $request): JsonResponse
    {
        $mount = Mount::query()->create($request->validated());

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a mount.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\UpdateMountRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateMountRequest $request, Mount $mount): array
    {
        $mount->update($request->validated());

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a mount.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\DeleteMountRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function delete(DeleteMountRequest $request, Mount $mount): JsonResponse
    {
        $mount->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddEggsRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function addEggs(MountAddEggsRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $eggs = $data['eggs'] ?? [];
        if (count($eggs) > 0) {
            $mount->eggs()->syncWithoutDetaching($eggs);
        }

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddNodesRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function addNodes(MountAddNodesRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $mount->nodes()->syncWithoutDetaching($nodes);
        }

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddEggsRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function deleteEggs(MountAddEggsRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $eggs = $data['eggs'] ?? [];
        if (count($eggs) > 0) {
            $mount->eggs()->detach($eggs);
        }

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Mounts\MountAddNodesRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function deleteNodes(MountAddNodesRequest $request, Mount $mount): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $mount->nodes()->detach($nodes);
        }

        return $this->fractal->item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toArray();
    }
}
