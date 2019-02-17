<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\User;

class UserFormRequest extends AdminFormRequest
{
    /**
     * Rules to apply to requests for updating or creating a user
     * in the Admin CP.
     */
    public function rules()
    {
        $rules = collect(User::getCreateRules());
        if ($this->method() === 'PATCH') {
            $rules = collect(User::getUpdateRulesForId($this->route()->parameter('user')->id))->merge([
                'ignore_connection_error' => ['sometimes', 'nullable', 'boolean'],
            ]);
        }

        return $rules->only([
            'email', 'username', 'oauth2_id', 'name_first', 'name_last', 'password',
            'language', 'ignore_connection_error', 'root_admin',
        ])->toArray();
    }
}
