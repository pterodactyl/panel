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
        $rules = collect(User::getRules());
        if ($this->method() === 'PATCH') {
            $rules = collect(User::getRulesForUpdate($this->route()->parameter('user')))->merge([
                'ignore_connection_error' => ['sometimes', 'nullable', 'boolean'],
            ]);
        }

        return $rules->only([
            'email', 'username', 'name_first', 'name_last', 'password',
            'language', 'ignore_connection_error', 'root_admin',
        ])->toArray();
    }
}
