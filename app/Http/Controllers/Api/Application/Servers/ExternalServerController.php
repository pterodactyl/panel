<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetExternalServerRequest;

class ExternalServerController extends ApplicationApiController
{
    /**
     * Retrieve a specific server from the database using its external ID.
     */
    public function index(GetExternalServerRequest $request): array
    {
        return $this->fractal->item($request->getServerModel())
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
