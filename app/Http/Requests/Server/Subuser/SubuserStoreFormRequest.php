<?php

namespace Pterodactyl\Http\Requests\Server\Subuser;

use Pterodactyl\Http\Requests\Server\ServerFormRequest;

class SubuserStoreFormRequest extends ServerFormRequest
{
    /**
     * Return the user permission to validate this request against.
     *
     * @return string
     */
    protected function permission(): string
    {
        return 'create-subuser';
    }

    /**
     * The rules to validate this request submission against.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'permissions' => 'sometimes|array',
        ];
    }
}
