<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nests;

use Pterodactyl\Models\Nest;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Nests\NestUpdateService;
use Pterodactyl\Services\Nests\NestCreationService;
use Pterodactyl\Services\Nests\NestDeletionService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\NestTransformer;
use Pterodactyl\Http\Requests\Api\Application\Nests\GetNestRequest;
use Pterodactyl\Http\Requests\Api\Application\Nests\GetNestsRequest;
use Pterodactyl\Http\Requests\Api\Application\Nests\StoreNestRequest;
use Pterodactyl\Http\Requests\Api\Application\Nests\UpdateNestRequest;
use Pterodactyl\Http\Requests\Api\Application\Nests\DeleteNestRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class NestController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Nests\NestCreationService
     */
    protected $nestCreationService;

    /**
     * @var \Pterodactyl\Services\Nests\NestDeletionService
     */
    protected $nestDeletionService;

    /**
     * @var \Pterodactyl\Services\Nests\NestUpdateService
     */
    protected $nestUpdateService;

    /**
     * NestController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $repository
     * @param \Pterodactyl\Services\Nests\NestCreationService $nestCreationService
     * @param \Pterodactyl\Services\Nests\NestDeletionService $nestDeletionService
     * @param \Pterodactyl\Services\Nests\NestUpdateService $nestUpdateService
     */
    public function __construct(
        NestRepositoryInterface $repository,
        NestCreationService $nestCreationService,
        NestDeletionService $nestDeletionService,
        NestUpdateService $nestUpdateService
    ) {
        parent::__construct();

        $this->repository = $repository;

        $this->nestCreationService = $nestCreationService;
        $this->nestDeletionService = $nestDeletionService;
        $this->nestUpdateService = $nestUpdateService;
    }

    /**
     * Return all Nests that exist on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\GetNestsRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetNestsRequest $request): array
    {
        $nests = $this->repository->paginated(10);

        return $this->fractal->collection($nests)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Return information about a single Nest model.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\GetNestRequest $request
     * @param \Pterodactyl\Models\Nest $nest
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetNestRequest $request, Nest $nest): array
    {
        return $this->fractal->item($nest)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new nest.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\StoreNestRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreNestRequest $request): array
    {
        $nest = $this->nestCreationService->handle($request->validated());

        return $this->fractal->item($nest)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Updates an existing nest.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\UpdateNestRequest $request
     * @param \Pterodactyl\Models\Nest $nest
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateNestRequest $request, Nest $nest): array
    {
        $this->nestUpdateService->handle($nest->id, $request->validated());

        return $this->fractal->item($nest)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Deletes an existing nest.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Nests\DeleteNestRequest $request
     * @param \Pterodactyl\Models\Nest $nest
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNestRequest $request, Nest $nest): JsonResponse
    {
        $this->nestDeletionService->handle($nest->id);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
