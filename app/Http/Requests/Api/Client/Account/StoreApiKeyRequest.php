<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreApiKeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        $rules = ApiKey::getRules();

        return [
            'description' => $rules['memo'],
            'allowed_ips' => $rules['allowed_ips'],
            'allowed_ips.*' => 'ip',
        ];
    }

    /**
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'allowed_ips.*' => 'All of the IP addresses entered must be valid IPv4 addresses.',
        ];
    }
}
