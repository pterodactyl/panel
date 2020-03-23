<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreApiKeyRequest extends ClientApiRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'description' => 'required|string|min:4',
            'allowed_ips' => 'array',
            'allowed_ips.*' => 'ip',
        ];
    }
}
