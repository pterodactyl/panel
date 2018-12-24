<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Nest;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreNestFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|nullable|string',
            'database_limit' => 'nullable|numeric',
            'allocation_limit' => 'nullable|numeric',
            'memory_monthly_cost' => 'nullable|numeric',
            'disk_monthly_cost' => 'nullable|numeric',
            'max_disk' => 'nullable|numeric',
            'cpu_limit' => 'nullable|numeric',
        ];
    }
}
