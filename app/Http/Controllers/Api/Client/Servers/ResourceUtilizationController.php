<?php

namespace App\Http\Controllers\Api\Client\Servers;

use App\Models\Server;
use App\Transformers\Api\Client\StatsTransformer;
use App\Http\Controllers\Api\Client\ClientApiController;
use App\Http\Requests\Api\Client\Servers\GetServerRequest;

class ResourceUtilizationController extends ClientApiController
{
    /**
     * Return the current resource utilization for a server.
     *
     * @param \App\Http\Requests\Api\Client\Servers\GetServerRequest $request
     * @return array
     */
    public function index(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
            ->transformWith($this->getTransformer(StatsTransformer::class))
            ->toArray();
    }
}
