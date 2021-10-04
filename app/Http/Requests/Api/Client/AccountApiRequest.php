<?php

namespace Pterodactyl\Http\Requests\Api\Client;

class AccountApiRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return '';
    }

    public function authorize(): bool
    {
        return true;
    }
}
