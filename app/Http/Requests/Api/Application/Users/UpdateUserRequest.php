<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Return the validation rules for this request.
     */
    public function rules(array $rules = null): array
    {
        $userId = $this->getModel(User::class)->id;

        return parent::rules(User::getRulesForUpdate($userId));
    }
}
