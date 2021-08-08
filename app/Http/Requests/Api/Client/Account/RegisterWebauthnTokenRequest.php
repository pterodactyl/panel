<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\AccountApiRequest;

class RegisterWebauthnTokenRequest extends AccountApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'register' => ['string', 'required'],
            'public_key' => ['string', 'required'],
        ];
    }
}
