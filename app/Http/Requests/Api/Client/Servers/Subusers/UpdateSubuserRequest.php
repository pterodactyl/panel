<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Models\Permission;

class UpdateSubuserRequest extends SubuserRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_USER_UPDATE;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}
