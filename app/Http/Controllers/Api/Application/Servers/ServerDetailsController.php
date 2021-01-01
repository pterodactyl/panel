<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest;

class ServerDetailsController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\BuildModificationService
     */
    private $buildModificationService;

    /**
     * @var \Pterodactyl\Services\Servers\DetailsModificationService
     */
    private $detailsModificationService;

    /**
     * ServerDetailsController constructor.
     *
     * @param \Pterodactyl\Services\Servers\BuildModificationService $buildModificationService
     * @param \Pterodactyl\Services\Servers\DetailsModificationService $detailsModificationService
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest $request
     * @param \Pterodactyl\Models\Server $server
     *
     * @return array
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Throwable
     */
    public function details(UpdateServerDetailsRequest $request, Server $server): array
    {
        $server = $this->detailsModificationService->returnUpdatedModel()->handle(
            $server, $request->validated()
        );

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Update the build details for a specific server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest $request
     * @param \Pterodactyl\Models\Server $server
     *
     * @return array
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function build(UpdateServerBuildConfigurationRequest $request, Server $server): array
    {
        $server = $this->buildModificationService->handle($server, $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
