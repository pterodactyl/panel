<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Illuminate\Support\Str;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Models\User;

class UserTransformer extends BaseAdminTransformer
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class],
    ];

    // Only list includes with an implemented include*() method; Fractal would 500 on an unimplemented one.
    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['servers'];

    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(User $user): array
    {
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
            'image' => 'https://gravatar.com/avatar/'.md5(Str::lower($user->email)),
            'servers_count' => (int) ($user->servers_count ?? 0),
            'subuser_of_count' => (int) ($user->subuser_of_count ?? 0),
            'created_at' => $this->formatTimestamp($user->created_at),
            'updated_at' => $this->formatTimestamp($user->updated_at),
            'relationships' => [],
        ];
    }

    public function includeServers(User $user): Collection|NullResource
    {
        $user->loadMissing('servers');

        return $this->collection(
            $user->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
