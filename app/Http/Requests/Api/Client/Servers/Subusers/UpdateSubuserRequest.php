<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Enum\Permissions;

class UpdateSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permissions::UserUpdate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}
