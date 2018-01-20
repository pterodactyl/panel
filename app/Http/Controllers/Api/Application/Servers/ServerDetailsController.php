<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest;

class ServerDetailsController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\DetailsModificationService
     */
    private $modificationService;

    /**
     * ServerDetailsController constructor.
     *
     * @param \Pterodactyl\Services\Servers\DetailsModificationService $modificationService
     */
    public function __construct(DetailsModificationService $modificationService)
    {
        parent::__construct();

        $this->modificationService = $modificationService;
    }

    /**
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest $request
     * @param \Pterodactyl\Models\Server                                                    $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function details(UpdateServerDetailsRequest $request, Server $server): array
    {
        $server = $this->modificationService->returnUpdatedModel()->handle($server, $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
