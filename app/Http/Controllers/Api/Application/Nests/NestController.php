<?php

namespace App\Http\Controllers\Api\Application\Nests;

use App\Models\Nest;
use App\Contracts\Repository\NestRepositoryInterface;
use App\Transformers\Api\Application\NestTransformer;
use App\Http\Requests\Api\Application\Nests\GetNestsRequest;
use App\Http\Controllers\Api\Application\ApplicationApiController;

class NestController extends ApplicationApiController
{
    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestController constructor.
     *
     * @param \App\Contracts\Repository\NestRepositoryInterface $repository
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all Nests that exist on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Nests\GetNestsRequest $request
     * @return array
     */
    public function index(GetNestsRequest $request): array
    {
        $nests = $this->repository->paginated(50);

        return $this->fractal->collection($nests)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Return information about a single Nest model.
     *
     * @param \App\Http\Requests\Api\Application\Nests\GetNestsRequest $request
     * @return array
     */
    public function view(GetNestsRequest $request): array
    {
        return $this->fractal->item($request->getModel(Nest::class))
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }
}
