<?php

namespace App\Http\Requests\Api\Application\Users;

use App\Models\User;
use App\Services\Acl\Api\AdminAcl;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalUserRequest extends ApplicationApiRequest
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested external user exists.
     *
     * @return bool
     */
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

    /**
     * Return the user model for the requested external user.
     * @return \App\Models\User
     */
    public function getUserModel(): User
    {
        return $this->userModel;
    }
}
