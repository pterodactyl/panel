<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetSubuserRequest extends ClientApiRequest
{
    /**
     * Confirm that a user is able to view subusers for the specified server.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('user.read', $this->route()->parameter('server'));
    }
}
