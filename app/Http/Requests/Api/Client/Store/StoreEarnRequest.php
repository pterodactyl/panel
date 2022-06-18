<?php

namespace Pterodactyl\Http\Requests\Api\Client\Store;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreEarnRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
