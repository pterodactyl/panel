<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Validation\UserRules;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Return the validation rules for this request.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        return parent::rules(UserRules::rules($this->parameter('user', User::class)));
    }
}
