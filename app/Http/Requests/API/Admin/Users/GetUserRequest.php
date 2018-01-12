<?php

namespace Pterodactyl\Http\Requests\API\Admin\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\API\Admin\ApiAdminRequest;

class GetUserRequest extends ApiAdminRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested user exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $user = $this->route()->parameter('user');

        return $user instanceof  User && $user->exists;
    }
}
