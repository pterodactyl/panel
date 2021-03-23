<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\EggTransformer;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggRequest;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggsRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\StoreEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\DeleteEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\UpdateEggRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class EggController extends ApplicationApiController
{
    private EggRepositoryInterface $repository;

    /**
     * EggController constructor.
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return an array of all eggs on a given nest.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetEggsRequest $request, Nest $nest): array
    {
        $perPage = $request->query('per_page', 0);
        if ($perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $eggs = QueryBuilder::for(Egg::query())
            ->allowedFilters(['id', 'name', 'author'])
            ->allowedSorts(['id', 'name', 'author']);
        if ($perPage > 0) {
            $eggs = $eggs->paginate($perPage);
        }

        return $this->fractal->collection($eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single egg.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetEggRequest $request, Egg $egg): array
    {
        return $this->fractal->item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new egg.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreEggRequest $request): JsonResponse
    {
        $egg = Egg::query()->create($request->validated());

        return $this->fractal->item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates an egg.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateEggRequest $request, Egg $egg): array
    {
        $egg->update($request->validated());

        return $this->fractal->item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Deletes an egg.
     *
     * @throws \Exception
     */
    public function delete(DeleteEggRequest $request, Egg $egg): Response
    {
        $egg->delete();

        return $this->returnNoContent();
    }
}
