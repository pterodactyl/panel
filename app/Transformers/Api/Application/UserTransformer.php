<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class UserTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
    ];

    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Return a transformed User model that can be consumed by external services.
     *
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
            'relationships' => [],
            'created_at' => $this->formatTimestamp($user->created_at),
            'updated_at' => $this->formatTimestamp($user->updated_at),
        ];
    }

    /**
     * Return the servers associated with this user.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeServers(User $user): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $user->loadMissing('servers');

        return $this->collection($user->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), 'server');
    }
}
