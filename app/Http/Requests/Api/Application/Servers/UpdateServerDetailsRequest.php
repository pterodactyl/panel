<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;

class UpdateServerDetailsRequest extends ServerWriteRequest
{
    /**
     * Rules to apply to a server details update request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = Server::getUpdateRulesForId($this->route()->parameter('server')->id);

        return [
            'name' => $rules['name'],
            'user' => $rules['owner_id'],
            'description' => array_merge(['nullable'], $rules['description']),
        ];
    }

    /**
     * Convert the posted data into the correct format that is expected
     * by the application.
     *
     * @return array
     */
    public function validated(): array
    {
        return [
            'name' => $this->input('name'),
            'owner_id' => $this->input('user'),
            'description' => $this->input('description'),
        ];
    }

    /**
     * Rename some of the attributes in error messages to clarify the field
     * being discussed.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'user' => 'User ID',
            'name' => 'Server Name',
        ];
    }
}
