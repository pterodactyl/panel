<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Service;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class ServiceFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|nullable|string',
            'folder' => 'required|regex:/^[\w.-]{1,50}$/|unique:services,folder',
            'startup' => 'required|nullable|string',
        ];

        if ($this->method() === 'PATCH') {
            $service = $this->route()->parameter('service');
            $rules['folder'] = $rules['folder'] . ',' . $service->id;

            return $rules;
        }

        return $rules;
    }
}
