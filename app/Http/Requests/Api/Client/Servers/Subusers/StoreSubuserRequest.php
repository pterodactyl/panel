<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Actions\Subusers\CreateSubuser;
use Pterodactyl\Enum\Permissions;

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
        return CreateSubuser::rules();
    }
}
