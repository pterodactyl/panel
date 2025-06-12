<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Transformers\Api\Application\HookTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class HookController extends ApplicationApiController
{
    /**
     * HookController constructor.
     */
    public function __construct(
        private HookRepository $hookRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Return all the hooks that currently exist on the server.
     */
    public function index(Request $request, $uuid): array
    {
        return $this->fractal->collection($this->hookRepository->findServerHooks($uuid))
            ->transformWith($this->getTransformer(HookTransformer::class))
            ->toArray();
    }

}
