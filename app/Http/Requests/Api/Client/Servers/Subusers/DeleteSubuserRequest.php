<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Models\Permission;

class DeleteSubuserRequest extends SubuserRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_USER_DELETE;
    }
}
