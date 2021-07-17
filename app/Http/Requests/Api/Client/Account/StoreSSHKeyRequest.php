<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreSSHKeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return UserSSHKey::getRules();
    }
}
