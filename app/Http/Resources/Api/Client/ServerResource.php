<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Services\Servers\StartupCommandService;
use UnexpectedValueException;

/**
 * @extends ClientResource<Server>
 */
#[ResponseField('server_identifier', schema: ['type' => 'string', 'pattern' => '^serv_[a-zA-Z0-9]+$', 'example' => 'serv_1a2b3c4d'])]
#[ResponseField('description', example: 'Survival world', nullable: true)]
#[ResponseField('status', schema: ['type' => 'string', 'nullable' => true, 'enum' => ['installing', 'install_failed', 'reinstall_failed', 'suspended', 'restoring_backup', null], 'example' => null])]
#[ResponseField('limits.threads', schema: ['type' => 'string', 'nullable' => true, 'example' => '1,2'])]
#[ResponseField('feature_limits.databases', 'integer', example: 5, nullable: true)]
#[ResponseField('feature_limits.allocations', 'integer', example: 5, nullable: true)]
#[ResponseField('feature_limits.backups', 'integer', example: 5, nullable: true)]
#[ResponseField('egg_features', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['eula']])]
#[ResponseField('egg_tags', schema: ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['minecraft']])]
class ServerResource extends ClientResource
{
    protected array $eagerLoads = ['egg.variables', 'egg.configFrom', 'egg.tags', 'serverVariables', 'node', 'transfer', 'subusers', 'allocation'];

    protected array $includeRelations = [
        'allocations' => ['relation' => 'allocations', 'resource' => AllocationResource::class],
        'egg' => ['relation' => 'egg', 'resource' => EggResource::class],
        'subusers' => ['relation' => 'subusers', 'resource' => SubuserResource::class],
    ];

    protected array $defaultIncludes = ['allocations', 'variables'];

    protected array $availableIncludes = ['egg', 'subusers'];

    public function resourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $user = $this->getUser();
        $server = $this->resource;

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
            'invocation' => App::make(StartupCommandService::class)->handle($server, ! $user->can(Permissions::StartupRead->value, $server)),
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

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'allocations' => $this->includeAllocations(),
            'variables' => $this->includeVariables(),
            'egg' => $this->includeEgg(),
            'subusers' => $this->includeSubusers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeAllocations(): ApiResourceCollection
    {
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if (! $this->getUser()->can(Permissions::AllocationRead->value, $this->resource)) {
            $primary = clone ($this->resource->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.'));
            $primary->notes = null;

            return $this->items([$primary], AllocationResource::class, Allocation::RESOURCE_NAME);
        }

        return $this->items($this->resource->loadMissing('allocations')->allocations, AllocationResource::class, Allocation::RESOURCE_NAME);
    }

    private function includeVariables(): ApiResourceCollection|NullResource
    {
        if (! $this->getUser()->can(Permissions::StartupRead->value, $this->resource)) {
            return $this->null();
        }

        return $this->items(
            App::make(EnvironmentService::class)->variables($this->resource)->where('user_viewable', true)->values(),
            EggVariableResource::class,
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * @return ApiResource<Egg>
     */
    private function includeEgg(): ApiResource
    {
        $egg = $this->resource->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');

        return $this->item($egg, EggResource::class, Egg::RESOURCE_NAME);
    }

    private function includeSubusers(): ApiResourceCollection|NullResource
    {
        if (! $this->getUser()->can(Permissions::UserRead->value, $this->resource)) {
            return $this->null();
        }

        return $this->items($this->resource->subusers, SubuserResource::class, Subuser::RESOURCE_NAME);
    }
}
