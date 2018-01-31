<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Determine if the requested user exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $user = $this->route()->parameter('user');

        return $user instanceof User && $user->exists;
    }

    /**
     * Return the validation rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = $this->route()->parameter('user')->id;

        return collect(User::getUpdateRulesForId($userId))->only([
            'external_id',
            'email',
            'username',
            'name_first',
            'name_last',
            'password',
            'language',
            'root_admin',
        ])->toArray();
    }
}
