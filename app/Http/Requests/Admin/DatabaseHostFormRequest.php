<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\DatabaseHost;

class DatabaseHostFormRequest extends AdminFormRequest
{
    /**
     * @return mixed
     */
    public function rules()
    {
        if (! $this->filled('node_id')) {
            $this->merge(['node_id' => null]);
        }

        if ($this->method() !== 'POST') {
            return DatabaseHost::getUpdateRulesForId($this->route()->parameter('host'));
        }

        return DatabaseHost::getCreateRules();
    }
}
