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

/**
 * @phpstan-type BuildAttributes array{allocation_id?: int, oom_disabled?: bool, memory?: int, swap?: int, io?: int, cpu?: int, threads?: string|null, disk?: int, database_limit: int|null, allocation_limit: int|null, backup_limit: int, add_allocations: list<int>, remove_allocations: list<int>}
 */
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
     * Validates build input for the server and returns the changes it describes. Only the
     * fields given are validated and changed, except the feature limits: a database or
     * allocation limit left out or null is cleared, and a backup limit becomes 0.
     *
     * @param  array<string, mixed>  $input
     * @return BuildAttributes
     */
    public static function attributes(array $input, Server $server): array
    {
        $rules = [];
        foreach (self::rules($server) as $field => $fieldRules) {
            $rules[$field] = ['sometimes', ...$fieldRules];
        }

        $input = Validator::make($input, $rules)->safe();

        $attributes = [
            'database_limit' => $input->filled('database_limit') ? $input->integer('database_limit') : null,
            'allocation_limit' => $input->filled('allocation_limit') ? $input->integer('allocation_limit') : null,
            'backup_limit' => $input->integer('backup_limit'),
            'add_allocations' => array_map(fn (int|string $key): int => $input->integer('add_allocations.'.$key), array_keys($input->array('add_allocations'))),
            'remove_allocations' => array_map(fn (int|string $key): int => $input->integer('remove_allocations.'.$key), array_keys($input->array('remove_allocations'))),
        ];

        if ($input->has('allocation_id')) {
            $attributes['allocation_id'] = $input->integer('allocation_id');
        }

        if ($input->has('oom_disabled')) {
            $attributes['oom_disabled'] = $input->boolean('oom_disabled');
        }

        foreach (['memory', 'swap', 'io', 'cpu', 'disk'] as $limit) {
            if ($input->has($limit)) {
                $attributes[$limit] = $input->integer($limit);
            }
        }

        if ($input->has('threads')) {
            $attributes['threads'] = $input->filled('threads') ? $input->string('threads')->toString() : null;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws DisplayException
     */
    public function update(Server $server, array $input): Server
    {
        $attributes = self::attributes($input, $server);

        // Allocation assignments and server attributes must change together.
        $server = DB::transaction(function () use ($server, $attributes): Server {
            $this->processAllocations($server, $attributes);
            if (isset($attributes['allocation_id']) && $attributes['allocation_id'] !== $server->allocation_id) {
                $allocationExists = Allocation::query()->where('id', $attributes['allocation_id'])
                    ->where('server_id', $server->id)->exists();
                throw_if(! $allocationExists, DisplayException::class, 'The requested default allocation is not currently assigned to this server.');
            }

            unset($attributes['add_allocations'], $attributes['remove_allocations']);
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

    /** @param BuildAttributes $attributes */
    private function processAllocations(Server $server, array &$attributes): void
    {
        if ($attributes['add_allocations'] === [] && $attributes['remove_allocations'] === []) {
            return;
        }

        if ($attributes['add_allocations'] !== []) {
            // Assign only currently unassigned allocations on this server's node.
            $assignable = Allocation::query()->where('node_id', $server->node_id)
                ->whereIn('id', $attributes['add_allocations'])->whereNull('server_id')
                ->orderBy('id')->lockForUpdate()->get(['id']);
            Allocation::query()->whereKey($assignable->modelKeys())->update(['server_id' => $server->id, 'notes' => null]);
            // If the default allocation is removed below, use the first newly assigned
            // allocation as its replacement.
            $freshlyAllocated = $assignable->first()?->id;
        }

        if ($attributes['remove_allocations'] !== []) {
            foreach ($attributes['remove_allocations'] as $allocation) {
                if ($allocation === ($attributes['allocation_id'] ?? $server->allocation_id)) {
                    throw_if(empty($freshlyAllocated), DisplayException::class, 'You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');
                    $attributes['allocation_id'] = $freshlyAllocated;
                }
            }

            // Release allocations after clearing notes so they do not leak to a future server.
            // Do not remove an allocation also requested in add_allocations.
            Allocation::query()->where('node_id', $server->node_id)
                ->where('server_id', $server->id)
                ->whereIn('id', array_diff($attributes['remove_allocations'], $attributes['add_allocations']))
                ->update(['notes' => null, 'server_id' => null]);
        }
    }
}
