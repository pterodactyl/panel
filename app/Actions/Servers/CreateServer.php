<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;
use Illuminate\Validation\Rule;
use Pterodactyl\Actions\Allocations\CreateAllocations;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Data\ValidatedEggVariable;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Ramsey\Uuid\Uuid;
use UnexpectedValueException;

final readonly class CreateServer implements CreatesServers
{
    public function __construct(
        private DeletesServers $deleteServer,
        private VariableValidatorService $validatorService,
        private ExtensionFields $extensions,
    ) {}

    /**
     * Memory, disk, CPU and the feature limits take 0 for unlimited, and swap takes -1. The CPU
     * threads are a pattern of cores such as "0-1,3", which a bare number also matches. Pass a
     * server being updated so its own external id is not taken for a duplicate.
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

    public function create(array $input, ?DeploymentObject $deployment = null): Server
    {
        $rules = self::rules();
        // A deployment picks the default allocation, so none is read from the input.
        if ($deployment instanceof DeploymentObject) {
            unset($rules['allocation_id']);
        }

        $validated = Validator::make($input, $rules)->safe();

        // Laravel's typed accessors would turn every value into one type, but the egg's
        // variable rules have to see each value as it was sent.
        $environment = [];
        foreach ($validated->array('environment') as $key => $value) {
            throw_unless(is_string($key) && ($value === null || is_scalar($value)), UnexpectedValueException::class, 'Server environment variables must map names to scalar values.');
            $environment[$key] = $value;
        }

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        $egg = Egg::query()->with('tags')->findOrFail($validated->integer('egg_id'));

        // Resolve the egg first because its tags constrain which nodes are viable.
        $allocation = $deployment instanceof DeploymentObject
            ? $this->configureDeployment($validated, $deployment, $egg)
            : Allocation::query()->findOrFail($validated->integer('allocation_id'));

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($egg, $environment);

        // Wings requires the server to exist in the Panel before it can start creation.
        $server = DB::transaction(function () use ($validated, $allocation, $eggVariableData, $extensions): Server {
            $server = $this->createModel($validated, $allocation);
            Allocation::query()->whereIn('id', [$allocation->id, ...$validated->array('allocation_additional')])->update(['server_id' => $server->id]);
            $this->storeEggVariables($server, $eggVariableData);
            $this->extensions->save($server, $extensions);

            return $server;
        }, 5);

        try {
            Daemon::server($server)->create($validated->boolean('start_on_completion'));
        } catch (DaemonConnectionException $daemonConnectionException) {
            // Remove the persisted record even when Wings could not be reached.
            $this->deleteServer->withForce()->delete($server);
            throw $daemonConnectionException;
        }

        Event::dispatch(new OperationCompleted($server->uuid, 'provision', true, $server->uuid));

        return $server;
    }

    private function configureDeployment(ValidatedInput $validated, DeploymentObject $deployment, Egg $egg): Allocation
    {
        $nodes = Node::query()
            ->viableFor($validated->integer('memory'), $validated->integer('disk'))
            ->inLocations($deployment->getLocations())
            ->forEggTags($egg->tagSlugs())
            ->forDeployTags($deployment->getTags())
            ->get()
            ->map(fn (Node $node): int => $node->id)
            ->all();

        throw_if($nodes === [], NoViableNodeException::class, trans('exceptions.deployment.no_viable_nodes'));

        return Allocation::query()
            ->unassigned()
            ->onNodes($nodes)
            ->onPorts($deployment->getPorts())
            ->when($deployment->isDedicated(), fn (Builder $query) => $query->onDedicatedIp($nodes))
            ->inRandomOrder()
            ->first() ?? throw new NoViableAllocationException(trans('exceptions.deployment.no_viable_allocations'));
    }

    /**
     * A server given no description gets an empty one, and a feature limit given as null or
     * left out is 0. A server given no OOM flag, or null, has the OOM killer disabled.
     */
    private function createModel(ValidatedInput $validated, Allocation $allocation): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        return Server::query()->create([
            'external_id' => $validated->filled('external_id') ? $validated['external_id'] : null,
            'uuid' => $uuid,
            'uuidShort' => mb_substr($uuid, 0, 8),
            'node_id' => $allocation->node_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'status' => Server::STATUS_INSTALLING,
            'skip_scripts' => $validated['skip_scripts'] ?? false,
            'owner_id' => $validated['owner_id'],
            'memory' => $validated['memory'],
            'swap' => $validated['swap'],
            'disk' => $validated['disk'],
            'io' => $validated['io'],
            'cpu' => $validated['cpu'],
            'threads' => $validated->filled('threads') ? $validated['threads'] : null,
            'oom_disabled' => $validated->filled('oom_disabled') ? $validated['oom_disabled'] : true,
            'allocation_id' => $allocation->id,
            'egg_id' => $validated['egg_id'],
            'startup' => $validated['startup'],
            'image' => $validated['image'],
            'database_limit' => $validated['database_limit'] ?? 0,
            'allocation_limit' => $validated['allocation_limit'] ?? 0,
            'backup_limit' => $validated['backup_limit'] ?? 0,
        ])->refresh();
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
