<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Http\Requests\Api\Client\AccountApiRequest;

class StoreApiKeyRequest extends AccountApiRequest
{
    public function rules(): array
    {
        return [
            'description' => PersonalAccessToken::getRules()['description'],
        ];
    }
}
