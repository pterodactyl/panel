<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
     * A server's build fields. The default allocation must exist and be no other server's
     * default; pass the server being updated so its own default is not taken for a clash.
     * The limits take the values a new server takes, and the allocations to add or remove
     * are lists of allocation ids.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Server $server = null): array
    {
        $rules = CreateServer::rules();

        return [
            'allocation_id' => ['required', 'bail', Rule::unique('servers', 'allocation_id')->ignore($server), 'exists:allocations,id'],
            'oom_disabled' => ['sometimes', 'boolean'],
            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'io' => $rules['io'],
            'cpu' => $rules['cpu'],
            'threads' => $rules['threads'],
            'disk' => $rules['disk'],
            'add_allocations' => ['bail', 'array', 'list'],
            'add_allocations.*' => ['integer'],
            'remove_allocations' => ['bail', 'array', 'list'],
            'remove_allocations.*' => ['integer'],
            'database_limit' => ['present', 'nullable', 'integer', 'min:0'],
            'allocation_limit' => $rules['allocation_limit'],
            'backup_limit' => ['present', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws DisplayException
     */
    public function update(Server $server, array $input): Server
    {
        $rules = array_map(fn (array $rules): array => ['sometimes', ...$rules], self::rules($server));
        $validator = Validator::make($input, $rules);
        $validated = $validator->safe();

        // Only the fields given change, apart from the feature limits: a database or allocation
        // limit left out or blank is cleared, and a backup limit left out becomes 0.
        $attributes = [
            ...$validator->safe(['oom_disabled', 'memory', 'swap', 'io', 'cpu', 'threads', 'disk']),
            'database_limit' => $validated->filled('database_limit') ? $validated['database_limit'] : null,
            'allocation_limit' => $validated->filled('allocation_limit') ? $validated['allocation_limit'] : null,
            'backup_limit' => $validated['backup_limit'] ?? 0,
        ];

        if ($validated->has('threads') && ! $validated->filled('threads')) {
            $attributes['threads'] = null;
        }

        // Allocations are matched by id, so they are read as integers.
        if ($validated->has('allocation_id')) {
            $attributes['allocation_id'] = $validated->integer('allocation_id');
        }

        $add = array_map(fn (int|string $key): int => $validated->integer('add_allocations.'.$key), array_keys($validated->array('add_allocations')));
        $remove = array_map(fn (int|string $key): int => $validated->integer('remove_allocations.'.$key), array_keys($validated->array('remove_allocations')));

        // Allocation assignments and server attributes must change together.
        $server = DB::transaction(function () use ($server, $attributes, $add, $remove): Server {
            $this->processAllocations($server, $add, $remove, $attributes);
            if (isset($attributes['allocation_id']) && $attributes['allocation_id'] !== $server->allocation_id) {
                $allocationExists = Allocation::query()->where('id', $attributes['allocation_id'])
                    ->where('server_id', $server->id)->exists();
                throw_if(! $allocationExists, DisplayException::class, 'The requested default allocation is not currently assigned to this server.');
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

    /**
     * @param  list<int>  $add
     * @param  list<int>  $remove
     * @param  array<string, mixed>  $attributes
     */
    private function processAllocations(Server $server, array $add, array $remove, array &$attributes): void
    {
        if ($add === [] && $remove === []) {
            return;
        }

        if ($add !== []) {
            // Assign only currently unassigned allocations on this server's node.
            $assignable = Allocation::query()->where('node_id', $server->node_id)
                ->whereIn('id', $add)->whereNull('server_id')
                ->orderBy('id')->lockForUpdate()->get(['id']);
            Allocation::query()->whereKey($assignable->modelKeys())->update(['server_id' => $server->id, 'notes' => null]);
            // If the default allocation is removed below, use the first newly assigned
            // allocation as its replacement.
            $freshlyAllocated = $assignable->first()?->id;
        }

        if ($remove !== []) {
            foreach ($remove as $allocation) {
                if ($allocation === ($attributes['allocation_id'] ?? $server->allocation_id)) {
                    throw_if(empty($freshlyAllocated), DisplayException::class, 'You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');
                    $attributes['allocation_id'] = $freshlyAllocated;
                }
            }

            // Release allocations after clearing notes so they do not leak to a future server.
            // Do not remove an allocation also requested in add_allocations.
            Allocation::query()->where('node_id', $server->node_id)
                ->where('server_id', $server->id)
                ->whereIn('id', array_diff($remove, $add))
                ->update(['notes' => null, 'server_id' => null]);
        }
    }
}
