<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Data\ServerCreationData;
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
use Ramsey\Uuid\Uuid;
use Throwable;

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
     * @param  ServerCreationInput  $data
     *
     * @throws Throwable
     * @throws DisplayException
     * @throws ValidationException
     * @throws NoViableNodeException
     * @throws NoViableAllocationException
     */
    public function create(array $data, ?DeploymentObject $deployment = null): Server
    {
        // Normalize the validated request once before applying any deployment rules.
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);
        $data = [...ServerCreationData::parse($data), 'extensions' => $extensions];
        throw_if(empty($data['egg_id']), InvalidArgumentException::class, 'Expected a non-empty egg_id in server creation data.');

        $egg = Egg::query()->with('tags')->findOrFail((int) $data['egg_id']);

        // Resolve the egg first because its tags constrain which nodes are viable.
        if ($deployment instanceof DeploymentObject) {
            $allocation = $this->configureDeployment($data, $deployment, $egg);
            $data['allocation_id'] = $allocation->id;
            $data['node_id'] = $allocation->node_id;
        }

        if (empty($data['node_id'])) {
            throw_if(empty($data['allocation_id']), InvalidArgumentException::class, 'Expected a non-empty allocation_id in server creation data.');
            $data['node_id'] = Allocation::query()->findOrFail((int) $data['allocation_id'])->node_id;
        }

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($egg, $data['environment']);

        // Wings requires the server to exist in the Panel before it can start creation.
        $server = DB::transaction(function () use ($data, $eggVariableData): Server {
            $server = $this->createModel($data);
            $this->storeAssignedAllocations($server, $data);
            $this->storeEggVariables($server, $eggVariableData);
            $this->extensions->save($server, $data['extensions']);

            return $server;
        }, 5);

        try {
            Daemon::server($server)->create($data['start_on_completion']);
        } catch (DaemonConnectionException $daemonConnectionException) {
            // Remove the persisted record even when Wings could not be reached.
            $this->deleteServer->withForce()->delete($server);
            throw $daemonConnectionException;
        }

        Event::dispatch(new OperationCompleted($server->uuid, 'provision', true, $server->uuid));

        return $server;
    }

    /** @param ServerCreationAttributes $data */
    private function configureDeployment(array $data, DeploymentObject $deployment, Egg $egg): Allocation
    {
        // Egg tags are matched against accepted game tags on viable nodes.
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk($data['disk'])
            ->setMemory($data['memory'])
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

    /** @param ServerCreationAttributes $data */
    private function createModel(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        return Server::query()->create([
            'external_id' => $data['external_id'],
            'uuid' => $uuid,
            'uuidShort' => mb_substr($uuid, 0, 8),
            'node_id' => $data['node_id'] ?? throw new InvalidArgumentException('Expected node_id in server creation data.'),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'status' => Server::STATUS_INSTALLING,
            'skip_scripts' => $data['skip_scripts'],
            'owner_id' => $data['owner_id'],
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'threads' => $data['threads'],
            'oom_disabled' => $data['oom_disabled'] ?? true,
            'allocation_id' => $data['allocation_id'],
            'egg_id' => $data['egg_id'],
            'startup' => $data['startup'],
            'image' => $data['image'],
            'database_limit' => $data['database_limit'] ?? 0,
            'allocation_limit' => $data['allocation_limit'] ?? 0,
            'backup_limit' => $data['backup_limit'] ?? 0,
        ])->refresh();
    }

    /** @param ServerCreationAttributes $data */
    private function storeAssignedAllocations(Server $server, array $data): void
    {
        $allocationId = $data['allocation_id'];
        throw_if($allocationId === null, InvalidArgumentException::class, 'Expected an allocation_id before storing server allocations.');
        $records = [$allocationId];
        if ($data['allocation_additional'] !== null) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        Allocation::query()->whereIn('id', $records)->update(['server_id' => $server->id]);
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
