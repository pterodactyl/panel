<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\EggTransformer;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggsRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\StoreEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\UpdateEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\DeleteEggRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class EggController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    private $repository;

    /**
     * EggController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return an array of all eggs on a given nest.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggsRequest $request
     * @param \Pterodactyl\Models\Nest $nest
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetEggsRequest $request, Nest $nest): array
    {
        $eggs = $this->repository->findWhere([
            ['nest_id', '=', $nest->id],
        ]);

        return $this->fractal->collection($eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single egg.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggRequest $request
     * @param \Pterodactyl\Models\Egg $egg
     *
     * @return array
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Eggs\StoreEggRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Eggs\UpdateEggRequest $request
     * @param \Pterodactyl\Models\Egg $egg
     *
     * @return array
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Eggs\DeleteEggRequest $request
     * @param \Pterodactyl\Models\Egg $egg
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function delete(DeleteEggRequest $request, Egg $egg): JsonResponse
    {
        $egg->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
