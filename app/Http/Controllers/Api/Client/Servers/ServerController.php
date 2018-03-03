<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest $request
     * @return array
     */
    public function index(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
