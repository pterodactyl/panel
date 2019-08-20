<?php

namespace App\Http\Controllers\Api\Client\Servers;

use App\Models\Server;
use App\Transformers\Api\Client\ServerTransformer;
use App\Http\Controllers\Api\Client\ClientApiController;
use App\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     *
     * @param \App\Http\Requests\Api\Client\Servers\GetServerRequest $request
     * @return array
     */
    public function index(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
