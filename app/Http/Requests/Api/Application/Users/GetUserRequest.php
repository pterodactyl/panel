<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;

class GetUserRequest extends GetUsersRequest
{
    /**
     * Determine if the requested role exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $user = $this->route()->parameter('user');

        return $user instanceof User && $user->exists;
    }
}
