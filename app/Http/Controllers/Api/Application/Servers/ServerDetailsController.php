<?php

namespace App\Http\Controllers\Api\Application\Servers;

use App\Models\Server;
use App\Services\Servers\BuildModificationService;
use App\Services\Servers\DetailsModificationService;
use App\Transformers\Api\Application\ServerTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest;
use App\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest;

class ServerDetailsController extends ApplicationApiController
{
    /**
     * @var \App\Services\Servers\BuildModificationService
     */
    private $buildModificationService;

    /**
     * @var \App\Services\Servers\DetailsModificationService
     */
    private $detailsModificationService;

    /**
     * ServerDetailsController constructor.
     *
     * @param \App\Services\Servers\BuildModificationService   $buildModificationService
     * @param \App\Services\Servers\DetailsModificationService $detailsModificationService
     */
    public function __construct(
        BuildModificationService $buildModificationService,
        DetailsModificationService $detailsModificationService
    ) {
        parent::__construct();

        $this->buildModificationService = $buildModificationService;
        $this->detailsModificationService = $detailsModificationService;
    }

    /**
     * Update the details for a specific server.
     *
     * @param \App\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest $request
     * @return array
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function details(UpdateServerDetailsRequest $request): array
    {
        $server = $this->detailsModificationService->returnUpdatedModel()->handle(
            $request->getModel(Server::class), $request->validated()
        );

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Update the build details for a specific server.
     *
     * @param \App\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest $request
     * @return array
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function build(UpdateServerBuildConfigurationRequest $request): array
    {
        $server = $this->buildModificationService->handle($request->getModel(Server::class), $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
