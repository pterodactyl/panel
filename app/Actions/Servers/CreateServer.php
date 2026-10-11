<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Actions\Allocations\CreateAllocations;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Data\ValidatedEggVariable;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Support\JsonValueGuard;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * @phpstan-type CreationAttributes array{external_id: string|null, name: string, description: string, owner_id: int, egg_id: int, image: string, startup: string, environment: array<string, bool|float|int|string|null>, skip_scripts: bool, oom_disabled: bool, memory: int, swap: int, disk: int, io: int, threads: string|null, cpu: int, database_limit: int, allocation_limit: int, backup_limit: int, allocation_id: int|null, allocation_additional: list<int>, start_on_completion: bool}
 */
final readonly class CreateServer implements CreatesServers
{
    public function __construct(
        private AllocationSelectionService $allocationSelectionService,
        private FindViableNodesService $findViableNodesService,
        private DeletesServers $deleteServer,
        private VariableValidatorService $validatorService,
        private ExtensionFields $extensions,
    ) {}

    /**
     * A new server's fields. The default allocation and any additional ones must exist and
     * belong to no server. Memory, disk, CPU and the feature limits take 0 for unlimited,
     * and swap takes -1. The CPU threads are a pattern of cores such as "0-1,3", which a
     * bare number also matches. Pass a server being updated so its own external id is not
     * taken for a duplicate.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Server $ignore = null): array
    {
        return [
            'external_id' => ['sometimes', 'nullable', 'string', 'between:1,191', Rule::unique('servers', 'external_id')->ignore($ignore)],
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'egg_id' => ['required', 'integer', 'exists:eggs,id'],
            'image' => ['required', 'string', 'max:191', 'regex:/^~?[\w\.\/\-:@ ]*$/'],
            'startup' => ['required', 'string'],
            'environment' => ['sometimes', 'array'],
            'skip_scripts' => ['sometimes', 'boolean'],
            'oom_disabled' => ['sometimes', 'nullable', 'boolean'],
            'memory' => ['required', 'integer', 'min:0'],
            'swap' => ['required', 'integer', 'min:-1'],
            'disk' => ['required', 'integer', 'min:0'],
            'io' => ['required', 'integer', 'between:10,1000'],
            'threads' => ['nullable', 'regex:/^[0-9-,]+$/'],
            'cpu' => ['required', 'integer', 'min:0'],
            'database_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allocation_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'backup_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allocation_id' => ['required', 'integer', 'bail', CreateAllocations::unassigned()],
            'allocation_additional' => ['sometimes', 'nullable', 'array', 'list'],
            'allocation_additional.*' => ['integer', CreateAllocations::unassigned()],
            'start_on_completion' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Validates server input and returns the server it describes. Given a deployment, the
     * deployment picks the default allocation, so none is read from the input.
     *
     * A server given no description gets an empty one, and a feature limit given as null or
     * left out is 0. A server given no OOM flag, or null, has the OOM killer disabled. The
     * environment keeps each value as it was given, for the egg's variable rules to check.
     *
     * @param  array<string, mixed>  $input
     * @return CreationAttributes
     */
    public static function attributes(array $input, ?DeploymentObject $deployment = null): array
    {
        $rules = self::rules();
        if ($deployment instanceof DeploymentObject) {
            unset($rules['allocation_id']);
        }

        $input = Validator::make($input, $rules)->safe();

        // Laravel's typed accessors would turn every value into one type, but the egg's
        // variable rules have to see each value as it was sent.
        $environment = $input->array('environment');
        JsonValueGuard::assertScalarPayload($environment);

        return [
            'external_id' => $input->filled('external_id') ? $input->string('external_id')->toString() : null,
            'name' => $input->string('name')->toString(),
            'description' => $input->string('description')->toString(),
            'owner_id' => $input->integer('owner_id'),
            'egg_id' => $input->integer('egg_id'),
            'image' => $input->string('image')->toString(),
            'startup' => $input->string('startup')->toString(),
            'environment' => $environment,
            'skip_scripts' => $input->boolean('skip_scripts'),
            'oom_disabled' => $input->filled('oom_disabled') ? $input->boolean('oom_disabled') : true,
            'memory' => $input->integer('memory'),
            'swap' => $input->integer('swap'),
            'disk' => $input->integer('disk'),
            'io' => $input->integer('io'),
            'threads' => $input->filled('threads') ? $input->string('threads')->toString() : null,
            'cpu' => $input->integer('cpu'),
            'database_limit' => $input->integer('database_limit'),
            'allocation_limit' => $input->integer('allocation_limit'),
            'backup_limit' => $input->integer('backup_limit'),
            'allocation_id' => $input->has('allocation_id') ? $input->integer('allocation_id') : null,
            'allocation_additional' => array_map(fn (int|string $key): int => $input->integer('allocation_additional.'.$key), array_keys($input->array('allocation_additional'))),
            'start_on_completion' => $input->boolean('start_on_completion'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     * @throws DisplayException
     * @throws ValidationException
     * @throws NoViableNodeException
     * @throws NoViableAllocationException
     */
    public function create(array $input, ?DeploymentObject $deployment = null): Server
    {
        $attributes = self::attributes($input, $deployment);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        $egg = Egg::query()->with('tags')->findOrFail($attributes['egg_id']);

        // Resolve the egg first because its tags constrain which nodes are viable.
        $allocation = $deployment instanceof DeploymentObject
            ? $this->configureDeployment($attributes, $deployment, $egg)
            : Allocation::query()->findOrFail($attributes['allocation_id']);

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($egg, $attributes['environment']);

        // Wings requires the server to exist in the Panel before it can start creation.
        $server = DB::transaction(function () use ($attributes, $allocation, $eggVariableData, $extensions): Server {
            $server = $this->createModel($attributes, $allocation);
            $this->storeAssignedAllocations($server, [$allocation->id, ...$attributes['allocation_additional']]);
            $this->storeEggVariables($server, $eggVariableData);
            $this->extensions->save($server, $extensions);

            return $server;
        }, 5);

        try {
            Daemon::server($server)->create($attributes['start_on_completion']);
        } catch (DaemonConnectionException $daemonConnectionException) {
            // Remove the persisted record even when Wings could not be reached.
            $this->deleteServer->withForce()->delete($server);
            throw $daemonConnectionException;
        }

        Event::dispatch(new OperationCompleted($server->uuid, 'provision', true, $server->uuid));

        return $server;
    }

    /** @param CreationAttributes $attributes */
    private function configureDeployment(array $attributes, DeploymentObject $deployment, Egg $egg): Allocation
    {
        // Egg tags are matched against accepted game tags on viable nodes.
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk($attributes['disk'])
            ->setMemory($attributes['memory'])
            ->setEggTags($egg->tagSlugs())
            ->setDeployTags($deployment->getTags())
            ->handle();

        $nodeIds = [];
        foreach ($nodes as $node) {
            $nodeIds[] = $node->id;
        }

        return $this->allocationSelectionService->setDedicated($deployment->isDedicated())
            ->setNodes($nodeIds)
            ->setPorts($deployment->getPorts())
            ->handle();
    }

    /** @param CreationAttributes $attributes */
    private function createModel(array $attributes, Allocation $allocation): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        return Server::query()->create([
            'external_id' => $attributes['external_id'],
            'uuid' => $uuid,
            'uuidShort' => mb_substr($uuid, 0, 8),
            'node_id' => $allocation->node_id,
            'name' => $attributes['name'],
            'description' => $attributes['description'],
            'status' => Server::STATUS_INSTALLING,
            'skip_scripts' => $attributes['skip_scripts'],
            'owner_id' => $attributes['owner_id'],
            'memory' => $attributes['memory'],
            'swap' => $attributes['swap'],
            'disk' => $attributes['disk'],
            'io' => $attributes['io'],
            'cpu' => $attributes['cpu'],
            'threads' => $attributes['threads'],
            'oom_disabled' => $attributes['oom_disabled'],
            'allocation_id' => $allocation->id,
            'egg_id' => $attributes['egg_id'],
            'startup' => $attributes['startup'],
            'image' => $attributes['image'],
            'database_limit' => $attributes['database_limit'],
            'allocation_limit' => $attributes['allocation_limit'],
            'backup_limit' => $attributes['backup_limit'],
        ])->refresh();
    }

    /** @param list<int> $allocationIds */
    private function storeAssignedAllocations(Server $server, array $allocationIds): void
    {
        Allocation::query()->whereIn('id', $allocationIds)->update(['server_id' => $server->id]);
    }

    /** @param Collection<int, ValidatedEggVariable> $variables */
    private function storeEggVariables(Server $server, Collection $variables): void
    {
        $records = [];
        foreach ($variables as $result) {
            $records[] = ['server_id' => $server->id, 'variable_id' => $result->id, 'variable_value' => $result->value ?? ''];
        }

        if ($records !== []) {
            ServerVariable::query()->insert($records);
        }
    }

    private function generateUniqueUuidCombo(): string
    {
        $uuid = Uuid::uuid4()->toString();
        if (Server::query()->where('uuid', $uuid)->orWhere('uuidShort', mb_substr($uuid, 0, 8))->exists()) {
            return $this->generateUniqueUuidCombo();
        }

        return $uuid;
    }
}
