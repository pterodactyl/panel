<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Permission;

class SendCommandRequest extends GetServerRequest
{
    /**
     * Determine if the API user has permission to perform this action.
     *
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_CONTROL_CONSOLE;
    }

    /**
     * Rules to validate this request against.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'command' => 'required|string|min:1',
        ];
    }
}
