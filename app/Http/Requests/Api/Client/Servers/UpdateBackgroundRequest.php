<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class UpdateBackgroundRequest extends ClientApiRequest
{
    /**
     * Determine if a client has permission to view this server on the API. This
     * should never be false since this would be checking the same permission as
     * resourceExists().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules to validate this request against.
     */
    public function rules(): array
    {
        return [
            'bg' => 'required|url',
        ];
    }
}
