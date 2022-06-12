<?php

namespace Pterodactyl\Http\Requests\Api\Client\Store\Gateways;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class PayPalRequest extends ClientApiRequest
{
    /**
     * Rules to validate this request against.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|int',
        ];
    }
}
