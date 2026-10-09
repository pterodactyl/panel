<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Servers\UpdatesServerBuild;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Throwable;

final readonly class UpdateServerBuild implements UpdatesServerBuild
{
    public function __construct(
        private ServerConfigurationStructureService $structureService,
    ) {}

    /**
     * @param  ServerBuildModificationData  $data
     *
     * @throws Throwable
     * @throws DisplayException
     */
    public function update(Server $server, array $data): Server
    {
        // Allocation assignments and server attributes must change together.
        $server = DB::transaction(function () use ($server, $data): Server {
            $this->processAllocations($server, $data);
            if (isset($data['allocation_id']) && $data['allocation_id'] !== $server->allocation_id) {
                $allocationExists = Allocation::query()->where('id', $data['allocation_id'])
                    ->where('server_id', $server->id)->exists();
                throw_if(! $allocationExists, DisplayException::class, 'The requested default allocation is not currently assigned to this server.');
            }

            // Only values present in the validated payload are changed. The three
            // feature limits keep their historical default behavior.
            $attributes = [
                'database_limit' => $data['database_limit'] ?? null,
                'allocation_limit' => $data['allocation_limit'] ?? null,
                'backup_limit' => $data['backup_limit'] ?? 0,
            ];
            foreach (['oom_disabled', 'memory', 'swap', 'io', 'cpu', 'threads', 'disk', 'allocation_id'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }

            $server->forceFill($attributes)->saveOrFail();

            return $server->refresh();
        });

        $updateData = $this->structureService->handle($server);
        if (! empty($updateData['build'])) {
            try {
                Daemon::server($server)->sync();
            } catch (DaemonConnectionException $exception) {
                // Wings fetches fresh Panel configuration when the server boots, so a
                // failed live sync can be logged without undoing the committed change.
                Log::warning($exception->getMessage(), ['exception' => $exception, 'server_id' => $server->id]);
            }
        }

        return $server;
    }

    /** @param ServerBuildModificationData $data */
    private function processAllocations(Server $server, array &$data): void
    {
        if (empty($data['add_allocations']) && empty($data['remove_allocations'])) {
            return;
        }

        if (! empty($data['add_allocations'])) {
            // Assign only currently unassigned allocations on this server's node.
            $assignable = Allocation::query()->where('node_id', $server->node_id)
                ->whereIn('id', $data['add_allocations'])->whereNull('server_id')
                ->orderBy('id')->lockForUpdate()->get(['id']);
            Allocation::query()->whereKey($assignable->modelKeys())->update(['server_id' => $server->id, 'notes' => null]);
            // If the default allocation is removed below, use the first newly assigned
            // allocation as its replacement.
            $freshlyAllocated = $assignable->first()?->id;
        }

        if (! empty($data['remove_allocations'])) {
            foreach ($data['remove_allocations'] as $allocation) {
                if ($allocation === ($data['allocation_id'] ?? $server->allocation_id)) {
                    throw_if(empty($freshlyAllocated), DisplayException::class, 'You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');
                    $data['allocation_id'] = $freshlyAllocated;
                }
            }

            // Release allocations after clearing notes so they do not leak to a future server.
            // Do not remove an allocation also requested in add_allocations.
            Allocation::query()->where('node_id', $server->node_id)
                ->where('server_id', $server->id)
                ->whereIn('id', array_diff($data['remove_allocations'], $data['add_allocations'] ?? []))
                ->update(['notes' => null, 'server_id' => null]);
        }
    }
}
