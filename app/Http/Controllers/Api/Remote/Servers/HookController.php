<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Hook;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Hooks\HookExecutionService;
use Pterodactyl\Transformers\Api\Application\HookTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class HookController extends ApplicationApiController
{
    /**
     * HookController constructor.
     */
    public function __construct(
        private HookRepository $hookRepository,
        private HookExecutionService $executionService,
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

    public function trigger(Request $request, $uuid): JsonResponse
    {
        $this->executionService->handle(Hook::where('id', '=', $request->input('id'))->first());
        return response()->json(["status"=>"success"]);
    }
}
