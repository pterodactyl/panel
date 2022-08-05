<?php

namespace Pterodactyl\Http\Requests\Api\Client\Store;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class CreateServerRequest extends ClientApiRequest
{
    /**
     * Rules to validate this request against.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:191',
            'description' => 'nullable|string|min:3|max:191',
            'cpu' => 'required|int|min:50',
            'memory' => 'required|numeric|min:1',
            'disk' => 'required|numeric|min:1',
            'ports' => 'required|int|min:1',
            'backups' => 'nullable|int',
            'databases' => 'nullable|int',
            'egg' => 'required|int|min:1',
            'nest' => 'required|int|min:1',
            'node' => 'required|int|min:1',
        ];
    }
}
