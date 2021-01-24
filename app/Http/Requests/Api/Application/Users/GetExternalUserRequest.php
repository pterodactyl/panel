<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalUserRequest extends ApplicationApiRequest
{
    private User $userModel;
    protected string $resource = AdminAcl::RESOURCE_USERS;
    protected int $permission = AdminAcl::READ;

    public function resourceExists(): bool
    {
        $repository = $this->container->make(UserRepositoryInterface::class);

        try {
            $this->userModel = $repository->findFirstWhere([
                ['external_id', '=', $this->route()->parameter('external_id')],
            ]);
        } catch (RecordNotFoundException $exception) {
            return false;
        }

        return true;
    }

    public function getUserModel(): User
    {
        return $this->userModel;
    }
}
