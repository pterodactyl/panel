<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Subuser>
 */
class SubuserResource extends ApplicationResource
{
    protected array $includeRelations = [
        'user' => ['relation' => 'user', 'resource' => UserResource::class, 'ability' => AdminAcl::RESOURCE_USERS],
        'server' => ['relation' => 'server', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    protected array $availableIncludes = ['user', 'server'];

    public function resourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'server_id' => $this->resource->server_id,
            'permissions' => $this->resource->permissions,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
            'updated_at' => $this->formatTimestamp($this->resource->updated_at),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'user' => $this->includeUser(),
            'server' => $this->includeServer(),
            default => parent::resolveInclude($include),
        };
    }

    /**
     * @return ApiResource<User>|NullResource
     */
    private function includeUser(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('user')->user, UserResource::class, 'user');
    }

    /**
     * @return ApiResource<Server>|NullResource
     */
    private function includeServer(): ApiResource|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->item($this->resource->loadMissing('server')->server, ServerResource::class, 'server');
    }
}
