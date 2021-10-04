<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Http\Requests\Api\Client\AccountApiRequest;

class StoreSSHKeyRequest extends AccountApiRequest
{
    public function rules(): array
    {
        return UserSSHKey::getRules();
    }
}
