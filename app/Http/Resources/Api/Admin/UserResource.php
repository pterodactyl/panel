<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\User;

/**
 * @extends AdminResource<User>
 */
class UserResource extends AdminResource
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class],
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
            'image' => $user->avatar_url ?? 'https://gravatar.com/avatar/'.md5(Str::lower($user->email)),
            'avatar' => $user->avatar_url,
            'servers_count' => (int) ($user->servers_count ?? 0),
            'subuser_of_count' => (int) ($user->subuser_of_count ?? 0),
            'created_at' => $this->formatTimestamp($user->created_at),
            'updated_at' => $this->formatTimestamp($user->updated_at),
            'relationships' => [],
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

    private function includeServers(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, 'server');
    }
}
