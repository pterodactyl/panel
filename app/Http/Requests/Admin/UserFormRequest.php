<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\User;

class UserFormRequest extends AdminFormRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            $rules = User::getUpdateRulesForId($this->route()->parameter('user')->id);

            return array_merge($rules, [
                'ignore_connection_error' => 'sometimes|nullable|boolean',
            ]);
        }

        return User::getCreateRules();
    }

    public function normalize($only = [])
    {
        if ($this->method === 'PATCH') {
            return array_merge(
                $this->all(['password']),
                $this->only(['email', 'username', 'name_first', 'name_last', 'root_admin', 'ignore_connection_error'])
            );
        }

        return parent::normalize();
    }
}
