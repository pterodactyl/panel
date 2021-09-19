<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\AccountApiRequest;

class RegisterWebauthnTokenRequest extends AccountApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'token_id' => ['required', 'string'],
            'registration' => ['required', 'array'],
            'registration.id' => ['required', 'string'],
            'registration.type' => ['required', 'in:public-key'],
            'registration.response.attestationObject' => ['required', 'string'],
            'registration.response.clientDataJSON' => ['required', 'string'],
        ];
    }
}
