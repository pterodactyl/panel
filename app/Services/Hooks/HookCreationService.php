<?php

namespace Pterodactyl\Services\Hooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Server;

class HookCreationService
{
    protected HookUpdateService $hookUpdateService;
    /**
     * HookCreationService constructor.
     */
    public function __construct(HookUpdateService $hookUpdateService)
    {
        $this->hookUpdateService = $hookUpdateService;
    }

    /**
     * Create a new hook.
     *
     */
    public function handle(Server $server, array $data): Hook
    {
        Log::info('Data:', ['data' => $data]);
        return DB::transaction(function () use ($server, $data) {
            $hook = Hook::create([
                "server_id" => $server->id,
                "name" => $data['name'],
                "enabled" => $data['enabled'] ?? false,
            ]);
            $this->hookUpdateService->handle($hook, $data);

            return $hook;
        });
    }
}
