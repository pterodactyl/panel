<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\User;
use Pterodactyl\Validation\UserRules;

class UpdateUserRequest extends StoreUserRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminUsersUpdate];
    }

    /**
     * Validation rules for updating a user.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        return parent::rules(UserRules::rules($this->parameter('user', User::class)));
    }
}
