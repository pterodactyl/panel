<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ShopRequest extends ClientApiRequest
{
    /**
     * @throws \Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        return true;
    }
}
