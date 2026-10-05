<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Rules\UserEmail;

class StoreSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permissions::UserCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:strict', 'between:1,191', new UserEmail],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}
