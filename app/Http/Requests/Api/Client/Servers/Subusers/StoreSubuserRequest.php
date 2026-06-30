<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Rules\UserEmail;
use Pterodactyl\Models\Permission;

class StoreSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permission::ACTION_USER_CREATE;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:strict', 'between:1,191', new UserEmail()],
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}
