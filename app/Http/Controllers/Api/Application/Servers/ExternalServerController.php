<?php

namespace App\Http\Controllers\Api\Application\Servers;

use App\Transformers\Api\Application\ServerTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Servers\GetExternalServerRequest;

class ExternalServerController extends ApplicationApiController
{
    /**
     * Retrieve a specific server from the database using its external ID.
     *
     * @param \App\Http\Requests\Api\Application\Servers\GetExternalServerRequest $request
     * @return array
     */
    public function index(GetExternalServerRequest $request): array
    {
        return $this->fractal->item($request->getServerModel())
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
