<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Users\UserAvatarService;

/**
 * @extends ApplicationResource<User>
 */
class UserResource extends ApplicationResource
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    protected array $availableIncludes = ['servers'];

    public function resourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'external_id' => $user->external_id,
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->name_first,
            'last_name' => $user->name_last,
            'language' => $user->language,
            'root_admin' => (bool) $user->root_admin,
            '2fa' => (bool) $user->use_totp,
            'image' => resolve(UserAvatarService::class)->url($user->avatar),
            'avatar' => resolve(UserAvatarService::class)->url($user->avatar),
            'relationships' => [],
            'created_at' => $this->formatTimestamp($user->created_at),
            'updated_at' => $this->formatTimestamp($user->updated_at),
            ...$this->extensionFields($user),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'servers' => $this->includeServers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeServers(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }
}
