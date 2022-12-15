<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Server;

class UpdateServerDetailsRequest extends ServerWriteRequest
{
    /**
     * Rules to apply to a server details update request.
     */
    public function rules(): array
    {
        $rules = Server::getRulesForUpdate($this->route()->parameter('server')->id);

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'user' => $rules['owner_id'],
            'description' => array_merge(['nullable'], $rules['description']),
        ];
    }

    /**
     * Convert the posted data into the correct format that is expected
     * by the application.
     *
     * @param string|null $key
     * @param string|array|null $default
     */
    public function validated($key = null, $default = null)
    {
        $data = [
            'external_id' => $this->input('external_id'),
            'name' => $this->input('name'),
            'owner_id' => $this->input('user'),
            'description' => $this->input('description'),
        ];

        return is_null($key) ? $data : Arr::get($data, $key, $default);
    }

    /**
     * Rename some of the attributes in error messages to clarify the field
     * being discussed.
     */
    public function attributes(): array
    {
        return [
            'user' => 'User ID',
            'name' => 'Server Name',
        ];
    }
}
