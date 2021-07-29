<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreApiKeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'description' => PersonalAccessToken::getRules()['description'],
        ];
    }
}
