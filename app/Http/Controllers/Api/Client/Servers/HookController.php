<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Exceptions\HookTriggerValidationException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\DeleteHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\StoreHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\UpdateHookRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\ViewHooksRequest;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Hooks\HookCreationService;
use Pterodactyl\Services\Hooks\HookDeletionService;
use Pterodactyl\Services\Hooks\HookExecutionService;
use Pterodactyl\Services\Hooks\HookUpdateService;

class HookController extends Controller
{
    protected HookCreationService $creationService;
    protected HookDeletionService $deletionService;
    protected HookUpdateService $updateService;
    protected HookExecutionService $executionService;


    public function __construct(HookCreationService $creationService, HookDeletionService $deletionService, HookUpdateService $updateService, HookExecutionService $executionService) {
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
        $this->executionService = $executionService;
    }

    public function index(ViewHooksRequest $request, Server $server) {
        return response()->json([
            "data"=> Hook::where('server_id', $server->id)->get()
        ]);
    }

    public function view(ViewHooksRequest $request, Server $server) {
        return response()->json([
            "data"=> Hook::where('server_id', $server->id)->get()
        ]);
    }

    public function execute(ViewHooksRequest $request, Server $server) {
        return response()->json(
            Hook::where('server_id', $server->id)->get()
        );
    }

    public function store(StoreHookRequest $request, Server $server) {
        //$validated = $request->validated();
        return response()->json($request->all());
// redo the hookstorerquest-validator
        try {
            $this->creationService->handle($server, $validated);
        } catch (HookTriggerValidationException $hookTriggerValidationException) {
            return response()->json([
                'message' => 'One or more triggers are invalid',
                'errors' => $hookTriggerValidationException->getErrors(),
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function update(UpdateHookRequest $request, Server $server, Hook $hook) {
        $validated = $request->validated();
        try {
            $this->updateService->handle($hook, $validated);
        } catch (HookTriggerValidationException $hookTriggerValidationException) {
            return response()->json([
                'message' => 'One or more triggers are invalid',
                'errors' => $hookTriggerValidationException->getErrors(),
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function delete(DeleteHookRequest $request, Server $server, Hook $hook) {
        $this->deletionService->handle($hook);
        return response()->noContent();
    }
}
