<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Transformers\Api\Application\HookTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class HookController extends ApplicationApiController
{
    /**
     * HookController constructor.
     */
    public function __construct(
        private HookRepository $hookRepository,
        private ServerRepository $repository
    )
    {
        parent::__construct();
    }

    /**
     * Return all the hooks that currently exist on the server.
     */
    public function index(Request $request, $uuid): array
    {
        $server = $this->repository->getByUuid($uuid);

        return $this->fractal->collection($this->hookRepository->findServerHooks($server->id))
            ->transformWith($this->getTransformer(HookTransformer::class))
            ->toArray();
    }

}
