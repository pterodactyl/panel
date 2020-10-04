<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\AdminRole;

class RoleFormRequest extends AdminFormRequest
{
    /**
     * Setup the validation rules to use for these requests.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return AdminRole::getRulesForUpdate($this->route()->parameter('mount')->id);
        }

        return AdminRole::getRules();
    }
}
