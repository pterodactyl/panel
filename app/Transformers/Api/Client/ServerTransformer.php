<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Services\Servers\StartupCommandService;
use UnexpectedValueException;

#[ResponseField('server_identifier', schema: ['type' => 'string', 'pattern' => '^serv_[a-zA-Z0-9]+$', 'example' => 'serv_1a2b3c4d'])]
#[ResponseField('description', example: 'Survival world', nullable: true)]
#[ResponseField('status', schema: ['type' => 'string', 'nullable' => true, 'enum' => ['installing', 'install_failed', 'reinstall_failed', 'suspended', 'restoring_backup', null], 'example' => null])]
#[ResponseField('limits.threads', schema: ['type' => 'string', 'nullable' => true, 'example' => '1,2'])]
#[ResponseField('feature_limits.databases', 'integer', example: 5, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 5, nullable: true)]
#[ResponseField('feature_limits.backups', 'integer', example: 5, nullable: true)]
#[ResponseField('egg_features', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['eula']])]
#[ResponseField('egg_tags', schema: ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['minecraft']])]
class ServerTransformer extends BaseClientTransformer
{
    protected array $eagerLoads = ['egg.variables', 'egg.configFrom', 'egg.tags', 'serverVariables', 'node', 'transfer', 'subusers', 'allocation'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'transformer' => AllocationTransformer::class],
        'egg' => ['relation' => 'egg', 'transformer' => EggTransformer::class],
        'subusers' => ['relation' => 'subusers', 'transformer' => SubuserTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $defaultIncludes = ['allocations', 'variables'];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['egg', 'subusers'];

    public function __construct(
        private readonly EnvironmentService $environmentService,
        private readonly StartupCommandService $startupCommandService,
    ) {}

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * Transform a server model into a representation that can be returned
     * to a client.
     *
     * @return ApiPayload
     */
    public function transform(Server $server): array
    {
        $user = $this->getUser();

        $server->loadMissing(['egg.tags', 'node', 'transfer']);
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');

        return [
            'server_owner' => $user->id === $server->owner_id,
            'identifier' => config('pterodactyl.features.new_server_identifiers')
                ? $server->identifier
                : $server->uuidShort,
            '__deprecated_uuid_short' => $server->uuidShort,
            'server_identifier' => $server->identifier,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'is_node_under_maintenance' => $server->node->isUnderMaintenance(),
            'sftp_details' => [
                'ip' => $server->node->fqdn,
                'port' => $server->node->daemonSFTP,
            ],
            'description' => $server->description,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'invocation' => $this->startupCommandService->handle($server, ! $user->can(Permissions::StartupRead->value, $server)),
            'docker_image' => $server->image,
            'egg_features' => $egg->inherit_features,
            'egg_tags' => $egg->tagSlugs(),
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'status' => $server->status,
            // This field is deprecated, please use "status".
            'is_suspended' => $server->isSuspended(),
            // This field is deprecated, please use "status".
            'is_installing' => ! $server->isInstalled(),
            'is_transferring' => ($server->transfer) !== null,
            'skip_scripts' => $server->skip_scripts,
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server): Collection
    {
        $transformer = $this->makeTransformer(AllocationTransformer::class);

        $user = $this->getUser();
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if (! $user->can(Permissions::AllocationRead->value, $server)) {
            $primary = clone ($server->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.'));
            $primary->notes = null;

            return $this->collection([$primary], $transformer, Allocation::RESOURCE_NAME);
        }

        return $this->collection($server->loadMissing('allocations')->allocations, $transformer, Allocation::RESOURCE_NAME);
    }

    /**
     * @throws InvalidTransformerLevelException
     */
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (! $this->getUser()->can(Permissions::StartupRead->value, $server)) {
            return $this->null();
        }

        return $this->collection(
            $this->environmentService->variables($server)->where('user_viewable', true)->values(),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * Returns the egg associated with this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeEgg(Server $server): Item
    {
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');

        return $this->item($egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (! $this->getUser()->can(Permissions::UserRead->value, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
