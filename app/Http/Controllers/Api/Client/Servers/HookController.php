<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\HookActionValidationException;
use Pterodactyl\Exceptions\HookTriggerValidationException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\DeleteHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\StoreHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\TriggerHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\UpdateHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\ViewHooksRequest;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Services\Hooks\HookCreationService;
use Pterodactyl\Services\Hooks\HookDeletionService;
use Pterodactyl\Services\Hooks\HookExecutionService;
use Pterodactyl\Services\Hooks\HookUpdateService;
use Pterodactyl\Transformers\Api\Client\HookTransformer;

class HookController extends ClientApiController
{
    public function __construct(
        private HookCreationService $creationService,
        private HookDeletionService $deletionService,
        private HookUpdateService $updateService,
        private HookExecutionService $executionService,
        private HookRepository $repository,
    ) {
        parent::__construct();
    }
    public function index(ViewHooksRequest $request, Server $server): array {
        return $this->fractal->collection(
            $this->repository->findServerHooks($server->id)
        )->transformWith($this->getTransformer(HookTransformer::class))->toArray();
    }

    public function view(ViewHooksRequest $request, Server $server) {
        return response()->json([
            "data"=> Hook::where('server_id', $server->id)->get()
        ]);
    }

    public function execute(TriggerHookRequest $request, Server $server, Hook $hook) {
        $this->executionService->handle($hook);
        return response()->json([
            "status" => "success",
        ]);
    }

    public function store(StoreHookRequest $request, Server $server) {
        $validated = $request->validated();
        try {
            $hook = $this->creationService->handle($server, $validated);
            return $this->fractal->item(
                $hook
            )->transformWith($this->getTransformer(HookTransformer::class))->toArray();
        } catch (HookTriggerValidationException | HookActionValidationException $e) {
            return response()->json([
                'message' => $e instanceof HookTriggerValidationException
                    ? 'One or more triggers are invalid'
                    : 'One or more actions are invalid',
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    public function update(UpdateHookRequest $request, Server $server, Hook $hook) {
        $validated = $request->validated();
        Log::info($validated);
        try {
            $hook = $this->updateService->handle($hook, $validated);
            return $this->fractal->item(
                $hook->fresh(['trigger', 'action'])
            )->transformWith($this->getTransformer(HookTransformer::class))->toArray();
        } catch (HookTriggerValidationException $e) {
            return response()->json([
                'message' => 'One or more triggers are invalid',
                'errors' => $e->getErrors(),
            ], 422);
        } catch (HookActionValidationException $e) {
            return response()->json([
                'message' => 'One or more actions are invalid',
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    public function delete(DeleteHookRequest $request, Server $server, Hook $hook) {
        $this->deletionService->handle($hook);
        return response()->noContent();
    }
}
