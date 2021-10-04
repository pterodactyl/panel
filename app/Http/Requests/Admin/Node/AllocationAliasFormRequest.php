<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Node;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AllocationAliasFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'alias' => 'present|nullable|string',
            'allocation_id' => 'required|numeric|exists:allocations,id',
        ];
    }
}
