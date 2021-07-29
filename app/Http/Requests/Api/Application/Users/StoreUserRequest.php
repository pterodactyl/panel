<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreUserRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_USERS;
    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        $rules = $rules ?? User::getRules();

        $response = collect($rules)->only([
            'external_id',
            'email',
            'username',
            'password',
            'language',
            'admin_role_id',
        ])->toArray();

        return $response;
    }

    public function attributes(): array
    {
        return [
            'external_id' => 'Third Party Identifier',
            'root_admin' => 'Root Administrator Status',
        ];
    }
}
